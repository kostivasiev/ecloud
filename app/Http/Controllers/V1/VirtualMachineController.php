<?php

namespace App\Http\Controllers\V1;

use App\Datastore\Exceptions\DatastoreInsufficientSpaceException;
use App\Datastore\Exceptions\DatastoreNotFoundException;
use App\Events\V1\ApplianceLaunchedEvent;
use App\Exceptions\V1\ApplianceServerLicenseNotFoundException;
use App\Exceptions\V1\EncryptionServiceNotEnabledException;
use App\Exceptions\V1\InsufficientCreditsException;
use App\Exceptions\V1\InsufficientResourceException;
use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\KingpinException;
use App\Exceptions\V1\ServiceResponseException;
use App\Exceptions\V1\ServiceTimeoutException;
use App\Exceptions\V1\ServiceUnavailableException;
use App\Exceptions\V1\SolutionNotFoundException;
use App\Exceptions\V1\TemplateNotFoundException;
use App\Models\V1\ActiveDirectoryDomain;
use App\Models\V1\Datastore;
use App\Models\V1\GpuProfile;
use App\Models\V1\Pod;
use App\Models\V1\PodTemplate;
use App\Models\V1\SolutionNetwork;
use App\Models\V1\SolutionSite;
use App\Models\V1\SolutionTemplate;
use App\Models\V1\Tag;
use App\Models\V1\Trigger;
use App\Models\V1\VirtualMachine;
use App\Resources\V1\VirtualMachineResource;
use App\Rules\V1\IsValidSSHPublicKey;
use App\Rules\V1\IsValidUuid;
use App\Services\AccountsService;
use App\Services\BillingService;
use App\Services\IntapiService;
use App\Solution\CanModifyResource;
use App\Solution\EncryptionBillingType;
use App\VM\ResizeCheck;
use App\VM\Status;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mustache_Engine;
use UKFast\Admin\Devices\AdminClient;
use UKFast\Api\Exceptions;
use UKFast\DB\Ditto\QueryTransformer;

class VirtualMachineController extends BaseController
{
    const HDD_MAX_SIZE_GB = 2500;

    /**
     * get VM by ID
     * @param Request $request
     * @param $vmId
     * @return mixed
     * @throws Exceptions\NotFoundException
     */
    public static function getVirtualMachineById(Request $request, $vmId)
    {
        $collection = VirtualMachine::withResellerId($request->user()->resellerId());

        if ($request->user()->isScoped()) {
            $collection->where('servers_active', '=', 'y');
        }

        $VirtualMachine = $collection->find($vmId);
        if (!$VirtualMachine) {
            throw new Exceptions\NotFoundException('Virtual Machine ID #' . $vmId . ' not found');
        }

        return $VirtualMachine;
    }

    /**
     * List all VM's
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $virtualMachinesQuery = $this->getVirtualMachines();

        (new QueryTransformer($request))
            ->config(VirtualMachine::class)
            ->transform($virtualMachinesQuery);

        return $this->respondCollection(
            $request,
            $virtualMachinesQuery->paginate($this->perPage)
        );
    }

    /**
     * List VM's
     * For admin list all except when $resellerId is passed in
     * @param null $resellerId
     * @param array $vmIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getVirtualMachines($vmIds = [])
    {
        $virtualMachineQuery = VirtualMachine::query();
        if (!empty($vmIds)) {
            $virtualMachineQuery->whereIn('servers_id', $vmIds);
        }

        if ($this->isAdmin) {
            if (!empty($this->resellerId)) {
                $virtualMachineQuery->withResellerId($this->resellerId);
            }

            // Return ALL VM's
            return $virtualMachineQuery;
        }

        $virtualMachineQuery->where('servers_active', '=', 'y');

        //For non-admin filter on reseller ID
        return $virtualMachineQuery->withResellerId($this->resellerId);
    }

    /**
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     */
    public function show(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachines = $this->getVirtualMachines([$vmId]);
        $virtualMachine = $virtualMachines->first();
        if (!$virtualMachine) {
            throw new Exceptions\NotFoundException("Virtual Machine '$vmId' Not Found");
        }

        return $this->respondItem(
            $request,
            $virtualMachine,
            200,
            VirtualMachineResource::class
        );
    }

    /**
     * Validates the solution id
     * @param Request $request
     * @param $vmId
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateVirtualMachineId(&$request, $vmId)
    {
        $request['vmId'] = $vmId;
        $this->validate($request, ['vmId' => 'required|integer']);
    }

    /**
     * Creates a Virtual Machine
     * @param Request $request
     * @param IntapiService $intapiService
     * @param AccountsService $accountsService
     * @param BillingService $billingService
     * @return \Illuminate\Http\Response
     * @throws ApplianceServerLicenseNotFoundException
     * @throws EncryptionServiceNotEnabledException
     * @throws Exceptions\BadRequestException
     * @throws Exceptions\ForbiddenException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\UnauthorisedException
     * @throws InsufficientCreditsException
     * @throws InsufficientResourceException
     * @throws ServiceResponseException
     * @throws ServiceUnavailableException
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     * @throws \App\Exceptions\V1\ApplianceNotFoundException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResourceException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResponseException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidRouteException
     * @throws Exceptions\PaymentRequiredException
     */
    public function create(
        Request $request,
        IntapiService $intapiService,
        AccountsService $accountsService,
        BillingService $billingService
    ) {
        // todo remove when burst/gpu VMs supported
        // - need to update `add_billing` step on create_vm automation
        if (!$this->isAdmin && in_array($request->input('environment'), ['Burst', 'GPU'])) {
            throw new Exceptions\ForbiddenException(
                $request->input('environment') . ' VM creation is temporarily disabled'
            );
        }

        // default validation
        $rules = [
            'environment' => ['required', 'in:Public,Hybrid,Private,Burst,GPU'],
            'pod_id' => ['required_if:environment,Public'],

            'name' => ['nullable', 'regex:/' . VirtualMachine::NAME_FORMAT_REGEX . '/'],
            'tags' => ['nullable', 'array'],

            // User must either specify a vm template or an appliance_id
            'template' => ['required_without:appliance_id'],
            'appliance_id' => ['required_without:template', new IsValidUuid()],

            'cpu' => ['required', 'integer'],
            'ram' => ['required', 'integer'],
            'hdd' => [
                'required_without:hdd_disks',
                'integer',
            ],
            'hdd_disks' => [
                'required_without:hdd',
                'array',
                "max:" . VirtualMachine::MAX_HDD_COUNT . "",
            ],
            'datastore_id' => ['nullable', 'integer'],
            'network_id' => ['nullable', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'ad_domain_id' => ['nullable', 'integer'],

            'ssh_keys' => ['nullable', 'array'],
            'ssh_keys.*' => [new IsValidSSHPublicKey()],

            'encrypt' => ['sometimes', 'boolean'],

            'gpu_profile' => ['required_if:environment,GPU', new IsValidUuid()]
        ];


        if (!$request->user()->isAdmin()) {
            $rules['hdd'] = [
                'required_without:hdd_disks',
                'integer',
                'max:' . static::HDD_MAX_SIZE_GB,
            ];
        }

        // Validate role is allowed
        if ($request->has('role')) {
            $rules['role'] = ['required', 'sometimes', 'in:' . implode(',', VirtualMachine::getRoles($this->isAdmin))];
        }

        // Check we either have template or appliance_id but not both
        if (!($request->has('template') xor $request->has('appliance_id'))) {
            throw new Exceptions\BadRequestException(
                'Virtual machines must be launched with either the appliance_id or template parameter'
            );
        }

        // Check we either have hdd or hdd_disks but not both
        if (!($request->has('hdd') xor $request->has('hdd_disks'))) {
            throw new Exceptions\BadRequestException(
                'Virtual machines must be launched with either the hdd or hdd_disks parameter'
            );
        }

        if ($request->has('gpu_profile') && $request->input('environment') != 'GPU') {
            throw new Exceptions\ForbiddenException(
                'gpu_profile can only be set when environment is GPU'
            );
        }

        if (in_array($request->input('environment'), ['Public', 'Burst'])) {
            $rules['hdd_iops'] = ['nullable', 'integer'];
            // todo check iops in allowed range

            $rules['backup'] = ['nullable', 'boolean'];
        } else {
            $rules['solution_id'] = ['required', 'integer', 'min:1'];
        }

        if ($request->has('tags')) {
            $rules['tags.*.key'] = [
                'required',
                'regex:/' . Tag::KEY_FORMAT_REGEX . '/'
            ];
            $rules['tags.*.value'] = [
                'required',
                'string'
            ];
        }

        if ($request->input('monitoring') === true) {
            $rules['monitoring-contacts'] = ['required', 'array'];
            $rules['monitoring-contacts.*'] = ['integer'];
        }

        $this->validate($request, $rules, [
            'role.required' => 'The selected role is invalid',
        ]);

        // environment specific validation
        $minCpu = VirtualMachine::MIN_CPU;
        $maxCpu = VirtualMachine::MAX_CPU;
        $minRam = VirtualMachine::MIN_RAM;
        $maxRam = VirtualMachine::MAX_RAM;
        $minHdd = VirtualMachine::MIN_HDD;
        $maxHdd = VirtualMachine::MAX_HDD;

        if ($request->input('environment') == 'Public') {
            if (!$request->user()->isScoped()) {
                // if admin reseller scope is empty, we won't know the owner for the new VM
                throw new Exceptions\UnauthorisedException('Unable to determine account id');
            }
            // check for demo accounts
            if ($accountsService->isDemoCustomer($request->user()->resellerId())) {
                throw new Exceptions\ForbiddenException(
                    'eCloud Public is not available to demo account users, please upgrade via MyUKfast'
                );
            }

            // check the customer has a valid payment method on their account
            if ($accountsService->getPaymentMethod($request->user()->resellerId()) == 'Credit Card') {
                $billingService->scopeResellerId($request->user()->resellerId())->verifyDefaultPaymentCard();
            }

            if ($request->has('encrypt')) {
                throw new EncryptionServiceNotEnabledException(
                    'Encryption service is not available for eCloud Public at this time.'
                );
            }

            if ($request->has('controlpanel_id') && !$this->isAdmin) {
                throw new Exceptions\BadRequestException(
                    'Legacy Control Panel installation is no longer available, please use a marketplace appliance.'
                );
            }

            $solution = null;
            $pod = Pod::findOrFail($request->input('pod_id'));

            if (!$pod->hasEnabledService('Public')) {
                throw new Exceptions\BadRequestException(
                    'eCloud Public is not available on the requested Pod'
                );
            }

            $datastore = Datastore::getPublicDefault($pod, ($request->input('backup') == true));
        } else {
            $solution = SolutionController::getSolutionById($request, $request->input('solution_id'));
            $pod = $solution->pod;

            // Check if the solution can modify resources
            (new CanModifyResource($solution))->validate();

            // get datastore
            if ($request->has('datastore_id')) {
                $datastore = Datastore::find($request->input('datastore_id'));
            } else {
                $datastore = Datastore::getDefault($solution->getKey(), $request->input('environment'));
            }

            if (!in_array($request->input('environment'), ['Burst', 'GPU'])) {
                // get available compute
                $maxRam = min($maxRam, $solution->ramAvailable());

                if ($maxRam < 1) {
                    throw new InsufficientResourceException($intapiService->getFriendlyError(
                        'host has insufficient ram, ' . $maxRam . 'GB remaining'
                    ));
                }

                $maxHdd = $datastore->usage->available;
                if ($maxHdd < 1) {
                    throw new InsufficientResourceException($intapiService->getFriendlyError(
                        'datastore has insufficient space, ' . $maxHdd . 'GB remaining'
                    ));
                }

                if (!$request->user()->isAdmin()) {
                    $maxHdd = min(static::HDD_MAX_SIZE_GB, $maxHdd);
                }

                if ($solution->isMultiSite()) {
                    $rules['site_id'] = ['required', 'integer'];
                }
            }


            if ($solution->isMultiNetwork()) {
                $rules['network_id'] = ['required', 'integer'];

                if (!$solution->hasMultipleNetworks()) {
                    unset($rules['network_id']);

                    $defaultNetwork = SolutionNetwork::withSolution($solution->getKey())->first();
                    $request->request->add(['network_id' => $defaultNetwork->getKey()]);
                }
            }

            // If encryption is enabled but no flag passed in, set to the solution default
            if ($solution->encryptionEnabled() && !$request->has('encrypt')) {
                $encrypt_vm = ($solution->ucs_reseller_encryption_default == 'Yes');
            }

            if ($request->has('encrypt')) {
                if (!$solution->encryptionEnabled()) {
                    throw new EncryptionServiceNotEnabledException(
                        'Encryption service is not enabled on this solution.'
                    );
                }
                $encrypt_vm = $request->input('encrypt');
            }

            // If PAYG encryption, check there are sufficient credits
            if (!empty($encrypt_vm) && ($solution->encryptionBillingType() == EncryptionBillingType::PAYG)) {
                $credits = $accountsService->scopeResellerId($solution->resellerId())->getVmEncryptionCredits();
                if (!$credits) {
                    throw new ServiceUnavailableException('Unable to load product credits.');
                }
                if ($credits->remaining < 1) {
                    throw new InsufficientCreditsException();
                }
            }
        }

        $rules['cpu'] = array_merge($rules['cpu'], [
            'min:' . $minCpu,
            'max:' . $maxCpu
        ]);

        $rules['ram'] = array_merge($rules['ram'], [
            'min:' . $minRam,
            'max:' . $maxRam
        ]);

        $insufficientSpaceMessage =
            'datastore has insufficient space, ' . $datastore->usage->available . 'GB remaining';
        $encryptionDatastoreSpaceMessage = '';

        // single disk vm requested
        if ($request->has('hdd')) {
            $rules['hdd'][] = 'max:' . $maxHdd;
            if (!$this->isAdmin) {
                $rules['hdd'][] = 'min:' . $minHdd;
            }

            // Encrypting a VM requires twice the space on the datastore
            if (!empty($encrypt_vm)) {
                $encryptionDatastoreSpaceMessage = '. Encrypted VM\'s require double requested storage space.';
                if (($request->input('hdd') * 2) > $datastore->usage->available) {
                    throw new InsufficientResourceException(
                        $intapiService->getFriendlyError($insufficientSpaceMessage)
                        . $encryptionDatastoreSpaceMessage
                    );
                }
            }
        }

        // multi-disk vm requested
        if ($request->has('hdd_disks')) {
            // validate disk names
            $rules['hdd_disks.*.name'] = [
                'required',
                'regex:/' . VirtualMachine::HDD_NAME_FORMAT_REGEX . '/'
            ];

            // todo check numbers are sequential?

            // validate disk capacity
            $rules['hdd_disks.*.capacity'] = [
                'required',
                'integer',
                'max:' . $maxHdd
            ];

            if (!$this->isAdmin) {
                $rules['hdd_disks.*.capacity'][] = 'min:' . $minHdd;
            }

            $capacityRequested = array_sum(array_column($request->input('hdd_disks'), 'capacity'));

            $capacityAllowed = $datastore->usage->available;

            if (in_array($request->input('environment'), ['Public', 'Burst'])) {
                $capacityAllowed = VirtualMachine::MAX_HDD * VirtualMachine::MAX_HDD_COUNT;
            }

            // Encrypting a VM requires twice the space on the datastore
            if (!empty($encrypt_vm)) {
                $capacityRequested *= 2;
                $encryptionDatastoreSpaceMessage = '. Encrypted VM\'s require double requested storage space.';
            }

            if ($capacityRequested > $capacityAllowed) {
                throw new InsufficientResourceException(
                    $intapiService->getFriendlyError($insufficientSpaceMessage)
                    . $encryptionDatastoreSpaceMessage
                );
            }
        }

        $this->validate($request, $rules);

        /**
         * Launch VM from Appliance
         */
        if ($request->has('appliance_id')) {
            if ($request->input('environment') == 'GPU') {
                throw new Exceptions\ForbiddenException(
                    'Launching of Appliances is not available using the GPU environment at this time.'
                );
            }

            $scriptRules = [];

            //Validate the appliance exists
            $appliance = ApplianceController::getApplianceById($request, $request->input('appliance_id'));

            $applianceVersion = $appliance->getLatestVersion();
            $applianceVersionData = $applianceVersion->getDataArray();

            // Load the VM template from the appliance version specification
            if (empty($applianceVersion->vm_template)) {
                throw new TemplateNotFoundException('Invalid Virtual Machine Template for Appliance');
            }
            $templateName = $applianceVersion->getTemplateName();

            // Sort the Appliance params from the Request (user input) into key => value and add back
            // onto our Request for easy validation
            $requestApplianceParams = [];
            foreach ($request->input('parameters', []) as $requestParam) {
                $requestApplianceParams[trim($requestParam['key'])] = $requestParam['value'];
                //Add prefixed param to request (to avoid conflicts)
                $request['appliance_param_' . trim($requestParam['key'])] = $requestParam['value'];
            }

            // Get the script parameters that we need from the latest version of teh appliance
            $parameters = $applianceVersion->getParameters();

            // For each of the script parameters build some validation rules
            foreach ($parameters as $parameterKey => $parameter) {
                $key = 'appliance_param_' . $parameterKey;
                $scriptRules[$key][] = ($parameter->required == 'Yes') ? 'required' : 'nullable';
                //validation rules regex
                if (!empty($parameters[$parameterKey]->validation_rule)) {
                    $scriptRules[$key][] = 'regex:' . $parameters[$parameterKey]->validation_rule;
                }

                // For data types String,Numeric,Boolean we can use Laravel validation
                switch ($parameters[$parameterKey]->type) {
                    case 'String':
                    case 'Numeric':
                    case 'Boolean':
                        $scriptRules[$key][] = strtolower($parameters[$parameterKey]->type);
                        break;
                    case 'Password':
                        $scriptRules[$key][] = 'string';
                }
            }

            $this->validate($request, $scriptRules);

            // Attempt to build the script
            $Mustache_Engine = new Mustache_Engine;

            $mustacheTemplate = $Mustache_Engine->loadTemplate($applianceVersion->script_template);

            $applianceScript = $mustacheTemplate->render($requestApplianceParams);

            // Load the appliance template - we can use this later for validating hdd size
            $template = PodTemplate::applianceTemplate($pod, $templateName);

            // Try to load the server license associated with the appliance version
            try {
                $serverLicense = $applianceVersion->getLicense();
                $template->serverLicense = $serverLicense;
            } catch (ApplianceServerLicenseNotFoundException $exception) {
                if ($this->isAdmin) {
                    throw new ApplianceServerLicenseNotFoundException(
                        $exception->getMessage()
                    );
                }

                Log::critical(
                    "Unable to launch VM using Appliance '" . $appliance->getKey() . "'': Appliance version '"
                    . $applianceVersion->getKey() . "' has no server license."
                );
                throw new ServiceUnavailableException(
                    "Unable to launch Appliance '" . $appliance->getKey() . "' at this time."
                );
            }
            $platform = $template->platform();
            $license = $template->license();
        }

        if ($request->has('template')) {
            $templateName = $request->input('template');

            if ($request->input('environment') == 'GPU') {
                // We Determine the GPU template based on the base template name and the profile
                $gpuProfile = $pod->gpuProfiles()->find($request->input('gpu_profile'));

                if (empty($gpuProfile)) {
                    throw new Exceptions\NotFoundException('gpu_profile \'' . $request->input('gpu_profile') . '\' was not found');
                }

                // Check we have enough GPU resources available to launch the VM
                $availableGpuPool = GpuProfile::gpuResourcePoolAvailability();

                $requiredUsage = $gpuProfile->getResourceAllocation();

                if ($requiredUsage > $availableGpuPool) {
                    throw new InsufficientResourceException(
                        'Insufficient GPU resources available to launch Virtual Machine at this time'
                    );
                }

                $template = PodTemplate::withFriendlyName($pod, $templateName);
                try {
                    $gpuTemplate = $template->getGpuVersion($gpuProfile);
                } catch (TemplateNotFoundException $exception) {
                    throw new TemplateNotFoundException('No GPU template found matching requested template and gpu_profile');
                } catch (\Exception $exception) {
                    throw new ServiceUnavailableException($exception->getMessage());
                }

                $templateName = $template->name;
            } else {
                // Validate the template exists: Try to load from both Solution & Pod templates
                $template = TemplateController::getTemplateByName(
                    $templateName,
                    $pod,
                    $solution
                );
            }

            $platform = $template->platform();
            $license = $template->license();
        }

        if ($request->has('computername')) {
            if ($platform == 'Linux') {
                $rules['computername'] = [
                    'regex:/' . VirtualMachine::HOSTNAME_FORMAT_REGEX . '/'
                ];
            } elseif ($platform == 'Windows') {
                $rules['computername'] = [
                    'regex:/' . VirtualMachine::NETBIOS_FORMAT_REGEX . '/'
                ];
            }
        }

        $this->validate($request, $rules);

        $post_data = array(
            'reseller_id' => !empty($solution) ? $solution->ucs_reseller_reseller_id : $request->user()->resellerId(),
            'ecloud_type' => $request->input('environment'),
            'ucs_reseller_id' => $request->input('solution_id'),
            'server_active' => true,

            'name' => $request->input('name'),
            'role' => $request->input('role'),
            'netbios' => $request->input('computername'),

            'submitted_by_type' => 'API Client',
            'submitted_by_id' => $request->user()->applicationId(),
            'launched_by' => '-5',
        );

        if ($request->input('environment') == 'Public') {
            $post_data['ucs_datacentre_id'] = $request->input('pod_id');
        }

        if (isset($gpuProfile)) {
            $post_data['gpu_profile_uuid'] = $gpuProfile->getKey();
        }

        if ($request->has('ssh_keys')) {
            if ($platform != 'Linux') {
                throw new Exceptions\BadRequestException("ssh_keys only supported for Linux VM's at this time");
            }
            $post_data['ssh_keys'] = $request->input('ssh_keys');
        }

        if (isset($encrypt_vm)) {
            $post_data['encrypt_vm'] = ($encrypt_vm) ? 'true' : 'false';
        }
        // set template
        $post_data['platform'] = $platform;
        $post_data['license'] = $license;

        if ($request->has('appliance_id')) {
            $post_data['template'] = $templateName;
            $post_data['template_type'] = 'system';
        }

        if ($request->has('template')) {
            if ($template->subType != 'Base') {
                $post_data['template'] = $templateName;

                if ($template->type != 'Solution') {
                    $post_data['template_type'] = 'system';
                }
            }
        }

        if ($request->has('template_password')) {
            $post_data['template_password'] = $request->input('template_password');
        }

        // set compute
        $post_data['cpus'] = $request->input('cpu');
        $post_data['ram_gb'] = $request->input('ram');

        // set storage
        $post_data['reseller_lun_id'] = $datastore->getKey();

        if ($request->has('hdd')) {
            $post_data['hdd_gb'] = $request->input('hdd');
        } elseif ($request->has('hdd_disks')) {
            $post_data['hdd_gb'] = [];

            foreach ($request->input('hdd_disks') as $disk) {
                $post_data['hdd_gb'][$disk['name']] = $disk['capacity'];
            }
            $post_data['hdd_gb'] = serialize($post_data['hdd_gb']);
        }

        if ($request->has('hdd_iops') && in_array($request->input('environment'), ['Public', 'Burst'])) {
            $post_data['hdd_iops'] = $request->input('hdd_iops');
        }

        // Check hdd capacity is >= template hdd
        $templateHDDs = collect($template->hard_drives);

        if ($request->has('hdd')) {
            $templatePrimaryHdd = (object)$templateHDDs->firstWhere('name', 'Hard disk 1');

            if (!$templatePrimaryHdd) {
                throw new ServiceResponseException('Unable to determine minimum size requirements for Hard disk 1');
            }

            if ($request->input('hdd') < $templatePrimaryHdd->capacitygb) {
                throw new Exceptions\BadRequestException(
                    'Insufficient hdd capacity requested. Please specify ' . $templatePrimaryHdd->capacitygb . ' or more.'
                );
            }
        }

        if ($request->has('hdd_disks')) {
            $requestHDDs = collect($request->input('hdd_disks'));

            // Loop over the template HDD's and match to the request HDD's. Check the requested capacity is >= the template capacity.
            $templateHDDs->each(function ($templateHdd) use ($requestHDDs) {
                $requestHdd = (object)$requestHDDs->firstWhere('name', $templateHdd->name);

                if (!$requestHdd) {
                    throw new Exceptions\BadRequestException(
                        'hdd_disks template requirements not met. Missing ' . $templateHdd->name
                    );
                }

                if ($requestHdd->capacity < $templateHdd->capacitygb) {
                    throw new Exceptions\BadRequestException(
                        'Insufficient capacity requested for ' . $requestHdd->name . '. Please specify ' . $templateHdd->capacitygb . ' or more.'
                    );
                }
            });
        }

        // set active directory domain
        if ($request->has('ad_domain_id')) {
            $domain = ActiveDirectoryDomain::withReseller($request->user()->resellerId())
                ->find($request->input('ad_domain_id'));

            if (is_null($domain)) {
                throw new Exceptions\BadRequestException(
                    "An Active Directory domain matching the requested ID was not found",
                    'ad_domain_id'
                );
            }

            $post_data['ad_domain_id'] = $request->input('ad_domain_id');
        }

        // set networking
        if ($request->has('network_id')) {
            $network = SolutionNetwork::withSolution($request->input('solution_id'))
                ->find($request->input('network_id'));

            if (is_null($network)) {
                throw new Exceptions\BadRequestException(
                    "A network matching the requested ID was not found",
                    'network_id'
                );
            }

            $post_data['internal_vlan'] = $network->vlan_number;
        }

        if ($request->has('external_ip_required')) {
            $post_data['external_ip_required'] = $request->input('external_ip_required');
        }

        // set nameservers
        if ($request->has('nameservers')) {
            $post_data['nameservers'] = $request->input('nameservers');
        }

        if ($request->has('site_id')) {
            $site = SolutionSite::withSolution($request->input('solution_id'))
                ->find($request->input('site_id'));

            if (is_null($site)) {
                throw new Exceptions\BadRequestException(
                    "A site matching the requested ID was not found",
                    'site_id'
                );
            }

            $post_data['ucs_site_id'] = $site->getKey();
        }


        // set support
        if ($request->input('support') === true) {
            $post_data['advanced_support'] = true;
        }

        if ($request->input('monitoring') === true) {
            $post_data['monitoring_enabled'] = true;
            $post_data['monitoring_contacts'] = $request->input('monitoring_contacts');
        }

        //set tags
        if ($request->has('tags')) {
            $post_data['tags'] = $request->input('tags');
        }

        // Do we have an appliance script?
        if ($request->has('appliance_id')) {
            $post_data['is_appliance'] = true;
            if (!empty($applianceScript)) {
                $post_data['appliance_bootstrap_script'] = base64_encode($applianceScript);
            }

            if (in_array('ukfast.license.legacy-cp-id', array_keys($applianceVersionData))) {
                $post_data['control_panel_id'] = $applianceVersionData['ukfast.license.legacy-cp-id'];
            }
        }

        //set bootstrap script
        if ($request->has('bootstrap_script')) {
            $post_data['bootstrap_script'] = base64_encode($request->input('bootstrap_script'));
        }

        if ($request->input('environment') == 'Public') {
            if ($request->input('backup') === true) {
                $post_data['backup_enabled'] = true;
            }

            if ($request->has('controlpanel_id')) {
                $post_data['control_panel_id'] = $request->input('controlpanel_id');
            }
        }

        // set billing options
        if ($request->input('environment') == 'Public') {
            $post_data['billing_type'] = 'PAYG';
            $post_data['billing_period'] = 'Month';
        }

        // todo remove debugging when ready to retest
//        print_r($post_data);
//        exit;
        // ---

        // schedule automation
        try {
            $intapiService->request('/automation/create_ucs_vmware_vm', [
                'form_params' => $post_data,
                'headers' => [
                    'Accept' => 'application/xml',
                ]
            ]);


            $intapiData = $intapiService->getResponseData();
        } catch (\Exception $exception) {
            throw new ServiceUnavailableException('Failed to create new virtual machine', null, 502);
        }

        if (!$intapiData->result) {
            $error_msg = $intapiService->getFriendlyError(
                is_array($intapiData->errorset->error) ?
                    end($intapiData->errorset->error) :
                    $intapiData->errorset->error
            );

            throw new ServiceResponseException($error_msg);
        }

        Log::info(
            'VirtualMachine Launched',
            [
                'id' => $intapiData->data->server_id,
                'type' => $post_data['ecloud_type'],

                'kong_request_id' => $request->header('Request-ID'),
                'kong_consumer_custom_id' => $request->header('X-consumer-custom-id'),
            ]
        );

        $virtualMachine = new VirtualMachine();
        $virtualMachine->servers_id = $intapiData->data->server_id;
        $virtualMachine->servers_status = $intapiData->data->server_status;

        $headers = [];
        if ($request->user()->isAdmin()) {
            $headers = [
                'X-AutomationRequestId' => $intapiData->data->automation_request_id
            ];
        }

        if (isset($appliance)) {
            Event::dispatch(new ApplianceLaunchedEvent($appliance));
        }

        // If PAYG encryption, assign credit. We need to do after the intapi call so we have the server id
        if (!empty($encrypt_vm)) {
            if ($solution->encryptionBillingType() == EncryptionBillingType::PAYG) {
                $result = $accountsService
                    ->scopeResellerId($solution->resellerId())
                    ->assignVmEncryptionCredit($virtualMachine->getKey());

                if (!$result) {
                    Log::critical(
                        'Failed to assign credit when launching encrypted Virtual Machine.',
                        [
                            'id' => $virtualMachine->getKey(),
                            'reseller_id' => $virtualMachine->servers_reseller_id
                        ]
                    );
                }
            }
        }

        // Add the VM credentials to the response
        $credentials = $intapiData->data->credentials;
        $response = $this->respondSave($request, $virtualMachine, 202, null, $headers);
        $content = $response->getOriginalContent();
        $content['data']['credentials'] = is_array($credentials) ? $credentials : [$credentials];

        $response->setContent($content);

        return $response;
    }

    /**
     * @param Request $request
     * @param IntapiService $intapiService
     * @param AccountsService $accountsService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\ForbiddenException
     * @throws Exceptions\NotFoundException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function destroy(Request $request, IntapiService $intapiService, AccountsService $accountsService, $vmId)
    {
        $refundCredit = false;
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachines()->find($vmId);
        if (!$virtualMachine) {
            throw new Exceptions\NotFoundException("Virtual Machine with ID '$vmId' not found");
        }

        //cant delete vm if its doing something that requires it to exist
        if (!$virtualMachine->canBeDeleted()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted with status of: ' . $virtualMachine->servers_status
            );
        }

        // Check if the solution can modify resources
        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();

            $refundCredit = (
                $virtualMachine->servers_encrypted == 'Yes'
                && $virtualMachine->solution->encryptionBillingType() == EncryptionBillingType::PAYG
            );
        }

        //server is in contract
        if (!$request->user()->isAdmin() && $virtualMachine->inContract()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, in contract until ' .
                date('d/m/Y', strtotime($virtualMachine->servers_contract_end_date))
            );
        }

        //server is a managed device
        if (!$request->user()->isAdmin() && $virtualMachine->isManaged()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, device is managed by UKFast'
            );
        }

        $post_data = [];
        if ($request->user()->isAdmin()) {
            $rules = [
                'reason' => ['sometimes', 'string'],
                'cancel_billing' => ['sometimes', 'boolean']
            ];

            $this->validate($request, $rules);

            if (!empty($request->input('reason'))) {
                $post_data['deleted_reason'] = $request->input('reason');
            }

            if (!empty($request->input('cancel_billing'))) {
                $post_data['cancel_without_charge'] = $request->input('cancel_billing');
            }
        }

        //schedule automation
        try {
            $automationRequestId = $intapiService->automationRequest(
                'delete_vm',
                'server',
                $virtualMachine->getKey(),
                $post_data,
                'ecloud_ucs_' . $virtualMachine->pod->getKey(),
                $request->user()->userId(),
                $request->user()->type()
            );
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule deletion request');
        }

        $virtualMachine->servers_status = 'Pending Deletion';
        if (!$virtualMachine->save()) {
            //Log::critical('');
        }

        Log::info(
            'VirtualMachine Deleted',
            [
                'id' => $virtualMachine->getKey(),
                'type' => $virtualMachine->type(),

                'kong_request_id' => $request->header('Request-ID'),
                'kong_consumer_custom_id' => $request->header('X-consumer-custom-id'),
            ]
        );

        if ($refundCredit) {
            $result = $accountsService
                ->scopeResellerId($virtualMachine->servers_reseller_id)
                ->refundVmEncryptionCredit($virtualMachine->getKey());

            if (!$result) {
                Log::critical(
                    'Failed to refund encryption credit when destroying Virtual Machine',
                    [
                        'id' => $virtualMachine->getKey(),
                        'reseller_id' => $virtualMachine->servers_reseller_id
                    ]
                );
            }
        }

        $headers = [];
        if ($request->user()->isAdmin()) {
            $headers = [
                'X-AutomationRequestId' => $automationRequestId
            ];
        }

        return $this->respondEmpty(202, $headers);
    }

    /**
     * Clone a VM
     * @param Request $request
     * @param IntapiService $intapiService
     * @param AccountsService $accountsService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws DatastoreInsufficientSpaceException
     * @throws DatastoreNotFoundException
     * @throws EncryptionServiceNotEnabledException
     * @throws Exceptions\ForbiddenException
     * @throws Exceptions\NotFoundException
     * @throws InsufficientCreditsException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResourceException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResponseException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidRouteException
     */
    public function clone(
        Request $request,
        IntapiService $intapiService,
        AccountsService $accountsService,
        $vmId
    ) {
        //Validation
        $rules = [
            'name' => ['nullable', 'regex:/' . VirtualMachine::NAME_FORMAT_REGEX . '/'],
            'encrypt' => ['sometimes', 'boolean']
        ];

        $this->validateVirtualMachineId($request, $vmId);
        $this->validate($request, $rules);

        //Load the vm to clone
        $virtualMachine = $this->getVirtualMachine($vmId);

        // VM cloning isn't available to Public/Burst VMs
        if (in_array($virtualMachine->type(), ['Public', 'Burst', 'GPU'])) {
            throw new Exceptions\ForbiddenException(
                $virtualMachine->type() . ' VM cloning is currently disabled'
            );
        }

        // Check if the solution can modify resources
        (new CanModifyResource($virtualMachine->solution))->validate();

        //Load the default datastore and check there's enough space
        //For Hybrid the default is the available datastore with the most free space
        $datastore = Datastore::getDefault($virtualMachine->servers_ecloud_ucs_reseller_id, $virtualMachine->type());

        if (!$datastore instanceof Datastore) {
            throw new DatastoreNotFoundException('Unable to load datastore');
        }

        $requiredSpace = $virtualMachine->servers_hdd;

        $assignEncryptionCredit = ($virtualMachine->servers_encrypted == 'Yes');

        if ($request->has('encrypt')) {
            if ($virtualMachine->type() == 'Public') {
                throw new EncryptionServiceNotEnabledException(
                    'Encryption service is not currently available for ' . $virtualMachine->type() . ' VM\'s'
                );
            }
            if (!$virtualMachine->solution->encryptionEnabled()) {
                throw new EncryptionServiceNotEnabledException(
                    'Encryption service is not enabled on this solution.'
                );
            }
            $postData['encrypt_vm'] = $assignEncryptionCredit = $request->input('encrypt');

            // If encryption change is required, we need twice the storage space on the datastore to perform the action
            if ($request->input('encrypt') != ($virtualMachine->servers_encrypted == 'Yes')) {
                $requiredSpace *= 2;
            }
        }

        if ($datastore->usage->available < $requiredSpace) {
            $message = 'Insufficient free space on selected datastore.' .
                ' Request required ' . $requiredSpace . 'GB, datastore has '
                . $datastore->usage->available . 'GB remaining';
            throw new DatastoreInsufficientSpaceException($message);
        }

        // We only need to deduct the encryption credit if the billing type is PAYG
        $assignEncryptionCredit = (
            $assignEncryptionCredit
            && ($virtualMachine->solution->encryptionBillingType() == EncryptionBillingType::PAYG)
        );

        // If we need to use encryption, check there are remaining credits before proceeding
        if ($assignEncryptionCredit) {
            $credits = $accountsService
                ->scopeResellerId($virtualMachine->solution->resellerId())
                ->getVmEncryptionCredits();
            if (!$credits) {
                throw new ServiceUnavailableException('Unable to load product credits.');
            }
            if ($credits->remaining < 1) {
                throw new InsufficientCreditsException();
            }
        }

        //OK, start the clone process ==
        //create new server record
        $postData['reseller_id'] = $virtualMachine->servers_reseller_id;
        $postData['reseller_lun_id'] = $datastore->getKey();
        $postData['ucs_reseller_id'] = $virtualMachine->servers_ecloud_ucs_reseller_id;
        $postData['launched_by'] = '-5';
        $postData['server_id'] = $virtualMachine->getKey();
        $postData['datastore'] = $datastore->reseller_lun_name;
        $postData['name'] = $request->input('name');
        $postData['server_active'] = true;

        try {
            $clonedVirtualMacineId = $intapiService->cloneVM($postData);
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Currently unable to clone virtual machines');
        }

        if (empty($clonedVirtualMacineId)) {
            throw new ServiceUnavailableException('Failed to prepare virtual machine for cloning');
        }

        //Load the cloned virtual machine
        try {
            $clonedVirtualMacine = $this->getVirtualMachine($clonedVirtualMacineId);
        } catch (Exceptions\NotFoundException $exception) {
            throw new ServiceUnavailableException('Cloned virtual machine failed to initialise');
        }

        // Assign encryption credit
        if ($assignEncryptionCredit) {
            $result = $accountsService
                ->scopeResellerId($clonedVirtualMacine->solution->resellerId())
                ->assignVmEncryptionCredit($clonedVirtualMacine->getKey());

            if (!$result) {
                Log::critical(
                    'Failed to assign credit when cloning encrypted Virtual Machine.',
                    [
                        'original_vm_id' => $virtualMachine->getKey(),
                        'new_vm_id' => $clonedVirtualMacine->getKey(),
                        'reseller_id' => $clonedVirtualMacine->servers_reseller_id
                    ]
                );
            }
        }

        $responseData = $intapiService->getResponseData();
        $automationRequestId = $responseData->data->automation_request_id;

        // Respond with the new machine id
        $headers = [];
        if ($request->user()->isAdmin()) {
            $headers = ['X-AutomationRequestId' => $automationRequestId];
        }

        $respondSave = $this->respondSave(
            $request,
            $clonedVirtualMacine,
            202,
            null,
            $headers,
            [],
            '/' . $request->segment(1) . '/vms/{vmId}'
        );

        $originalLocation = $respondSave->original['meta']['location'];

        //Set the meta location to point to the new clone instead of the current resource
        $respondSave->original['meta']['location'] = substr($originalLocation, 0, strrpos($originalLocation, '/') + 1)
            . $clonedVirtualMacineId;

        $respondSave->setContent($respondSave->original);

        return $respondSave;
    }

    /**
     * Get a VM (Model, not query builder - use for updates etc)
     * @param $vmId int ID of the VM to return
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     * @throws Exceptions\NotFoundException
     */
    protected function getVirtualMachine($vmId)
    {
        // Load the VM
        $virtualMachineQuery = $this->getVirtualMachines([$vmId]);
        $VirtualMachine = $virtualMachineQuery->first();
        if (!$VirtualMachine) {
            throw new Exceptions\NotFoundException("The Virtual Machine '$vmId' Not Found");
        }
        return $VirtualMachine;
    }

    /**
     * Update virtual machine
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\BadRequestException
     * @throws Exceptions\DatabaseException
     * @throws Exceptions\ForbiddenException
     * @throws Exceptions\NotFoundException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \App\VM\Exceptions\InvalidVmStateException
     */
    public function update(Request $request, IntapiService $intapiService, $vmId)
    {
        $resizeRequired = false;
        /**
         * This endpoint should be using HTTP PATCH, log if we detect any PUT requests.
         */
        if ($request->method() == 'PUT') {
            Log::notice('Call to update VM endpoint using PUT detected. Request should be using PATCH');
        }

        $rules = [
            'name' => ['nullable', 'regex:/' . VirtualMachine::NAME_FORMAT_REGEX . '/'],
            'cpu' => ['nullable', 'integer'],
            'ram' => ['nullable', 'integer'],
            'hdd_disks' => ['nullable', 'array']
        ];

        if ($request->has('role')) {
            $rules['role'] = ['required', 'sometimes', 'in:' . implode(',', VirtualMachine::getRoles($this->isAdmin))];
        }

        $this->validateVirtualMachineId($request, $vmId);

        //Load the VM
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->isManaged()) {
            throw new Exceptions\ForbiddenException('Access to modify UKFast managed devices is restricted');
        }

        // todo remove when burst VMs supported, missing billing step on automation
        if (in_array($virtualMachine->type(), ['Burst']) && !$this->isAdmin) {
            throw new Exceptions\ForbiddenException(
                $virtualMachine->type() . ' VM updates are temporarily disabled'
            );
        }

        $this->validate($request, $rules, [
            'role.required' => 'The selected role is invalid',
        ]);

        //Define the min/max default sizes
        $minCpu = VirtualMachine::MIN_CPU;
        $maxCpu = VirtualMachine::MAX_CPU;
        $minRam = VirtualMachine::MIN_RAM;
        $maxRam = VirtualMachine::MAX_RAM;
        $minHdd = VirtualMachine::MIN_HDD;
        $maxHdd = VirtualMachine::MAX_HDD;

        switch ($virtualMachine->type()) {
            case 'Hybrid':
            case 'Private':
                // Check if the solution can modify resources
                (new CanModifyResource($virtualMachine->solution))->validate();

                $maxRam = intval($virtualMachine->servers_memory)
                    + min(VirtualMachine::MAX_RAM, $virtualMachine->solution->ramAvailable());

                // Get the vm's datastore
                $vmDatastore = $virtualMachine->getDatastore();
                if (!$vmDatastore) {
                    Log::error('Failed to retrieve datastore information from VMWare for VM #' . $virtualMachine->id);
                    throw new DatastoreNotFoundException('Unable to load datastore for virtual machine');
                }

                // Load the datastore object and get the vmware usage (so we take into account over provisioning)
                $datastoreQuery = Datastore::query()->withName($vmDatastore->name);

                if ($datastoreQuery->count() < 1) {
                    Log::error('Failed to retrieve datastore record \'' . $vmDatastore->name . '\'  from the database');
                    throw new DatastoreNotFoundException('Unable to load datastore for virtual machine');
                }

                $datastore = $datastoreQuery->first();
                $datastore->getVmwareUsage();

                // Max HDD size is the available datastore space + the current HDD size
                $maxHdd = ($datastore->vmwareUsage->available + $virtualMachine->servers_hdd);

                //TODO: Is this still right? should this be VirtualMachine::MIN_HDD
//                $minHdd = $virtualMachine->servers_hdd;
                break;

            case 'Public':
                if ($virtualMachine->isContract()) {
                    //Determine contract specific limits
                    $contractCpuTrigger = $virtualMachine->trigger('cpu');
                    $contractRamTrigger = $virtualMachine->trigger('ram');
                    $contractHddTrigger = $virtualMachine->trigger('hdd');

                    $minCpu = $this->extractContractTriggerCPUValue($contractCpuTrigger);
                    $minRam = $this->extractContractTriggerRAMValue($contractRamTrigger);
                    $minHdd = $this->extractContractTriggerHDDValue($contractHddTrigger);
                }
                break;

            case 'Burst':
                // Check if the solution can modify resources
                (new CanModifyResource($virtualMachine->solution))->validate();
                break;
            default:
        }

        $automationData = [];

        // Name
        // We can change the server name/role in realtime but Compute and Storage changes via automation.
        // If we are making other changes return 202 otherwise return 200
        if ($request->has('name')) {
            $virtualMachine->servers_friendly_name = $request->input('name');
        }

        if ($request->has('role')) {
            $virtualMachine->servers_role = $request->input('role');
        }

        if (!$virtualMachine->save()) {
            throw new Exceptions\DatabaseException('Failed to update virtual machine record');
        }

        // CPU
        if ($request->has('cpu')) {
            if ($request->input('cpu') < $minCpu) {
                throw new Exceptions\ForbiddenException('cpu value must be ' . $minCpu . ' or larger');
            }

            if ($request->input('cpu') > $maxCpu) {
                throw new Exceptions\ForbiddenException('cpu value must be ' . $maxCpu . ' or smaller');
            }
            $resizeRequired = ($request->input('cpu') != $virtualMachine->servers_cpu);
        }
        $automationData['cpu'] = $request->input('cpu', $virtualMachine->servers_cpu);

        // RAM
        if ($request->has('ram')) {
            if ($request->input('ram') < $minRam) {
                throw new Exceptions\ForbiddenException('ram value must be ' . $minRam . ' or larger');
            }

            if ($request->input('ram') > $maxRam) {
                throw new Exceptions\ForbiddenException('ram value must be ' . $maxRam . ' or smaller');
            }
            $resizeRequired = (($request->input('ram') != $virtualMachine->servers_memory) || $resizeRequired);
        }
        $automationData['ram'] = $request->input('ram', $virtualMachine->servers_memory);

        // Get the VM's active disks from vmware
        $disks = $virtualMachine->getActiveHDDs();
        $existingDisks = [];
        if ($disks !== false) {
            foreach ($disks as $disk) {
                $existingDisks[$disk->uuid] = $disk;
            }
        }

        // HDD
        $automationData['hdd'] = [];
        $totalCapacity = 0;

        if ($request->filled('hdd_disks')) {
            $newDisksCount = 0;
            foreach ($request->input('hdd_disks') as $hdd) {
                $hdd = (object)$hdd;

                if (!$request->user()->isAdmin()) {
                    if ($hdd->capacity > static::HDD_MAX_SIZE_GB) {
                        throw new Exceptions\BadRequestException(
                            'HDD with UUID ' . $hdd->uuid . ' cannot exceed ' . static::HDD_MAX_SIZE_GB . 'GB'
                        );
                    }
                }

                $isExistingDisk = false;
                if (isset($hdd->uuid)) {
                    // existing disks
                    $isExistingDisk = array_key_exists($hdd->uuid, $existingDisks);
                    if (!$isExistingDisk) {
                        throw new Exceptions\BadRequestException("HDD with UUID '" . $hdd->uuid . "' was not found");
                    }
                }

                if ($isExistingDisk) {
                    $hdd->name = $existingDisks[$hdd->uuid]->name;

                    //Add disks marked as deleted (state = 'absent') to automation data
                    if (isset($hdd->state) && $hdd->state == 'absent') {
                        if ($hdd->name == 'Hard disk 1' || ($hdd->uuid == $disks[0]->uuid)) {
                            // Don't allow deletion of the primary hard disk
                            $message = 'Primary hard disk (Hard disk 1) can not be deleted';
                            throw new Exceptions\ForbiddenException($message);
                        }

                        // Don't allow deletion of Hard disk 2 on VM's with legacy LVM
                        if ($hdd->name == 'Hard disk 2' && $virtualMachine->hasLegacyLVM()) {
                            $message = 'Unable to delete Hard Disk 2 on VMs with legacy LVM';
                            throw new Exceptions\ForbiddenException($message);
                        }

                        // Don't allow deletion of RDM disks
                        if ($existingDisks[$hdd->uuid]->type != 'Flat') {
                            $message = 'Unable to delete cluster disk (' . $hdd->name . ')';
                            throw new Exceptions\ForbiddenException($message);
                        }

                        $resizeRequired = true;
                        $hdd->capacity = 'deleted';
                        $automationData['hdd'][$hdd->name] = $hdd;
                        continue;
                    }

                    //Non-deleted disks
                    if (!is_numeric($hdd->capacity)) {
                        throw new Exceptions\BadRequestException("Invalid capacity for HDD '" . $hdd->uuid . "'");
                    }

                    if ($hdd->capacity < $existingDisks[$hdd->uuid]->capacity) {
                        $message = 'We are currently unable to shrink HDD capacity, ';
                        $message .= "HDD '" . $hdd->uuid . "' value must be larger than ";
                        $message .= $existingDisks[$hdd->uuid]->capacity . "GB";
                        throw new Exceptions\ForbiddenException($message);
                    }

                    // Prevent expand of Hard disk 1 for VM's with legacy LVM
                    if ($virtualMachine->hasLegacyLVM()
                        && $hdd->name == 'Hard disk 1'
                        && $hdd->capacity > $existingDisks[$hdd->uuid]->capacity) {
                        $message = 'Unable to expand Hard Disk 1 on VMs with legacy LVM';
                        throw new Exceptions\ForbiddenException($message);
                    }

                    // Prevent expand of RDM disks
                    if ($existingDisks[$hdd->uuid]->type != 'Flat'
                        && $hdd->capacity > $existingDisks[$hdd->uuid]->capacity) {
                        $message = 'We are currently unable to expand cluster disks , ';
                        $message .= "HDD '" . $hdd->uuid . "' value must be set to ";
                        $message .= $existingDisks[$hdd->uuid]->capacity . "GB";
                        throw new Exceptions\ForbiddenException($message);
                    }

                    //disk isn't changed
                    if ($hdd->capacity == $existingDisks[$hdd->uuid]->capacity) {
                        $totalCapacity += $hdd->capacity;
                        $hdd->state = 'present'; // For when we update the automation
                        $automationData['hdd'][$hdd->name] = $hdd;
                        continue;
                    }
                }

                // New disks must be prefixed with 'New '
                if (!$isExistingDisk) {
                    // For now, we still need hdd in the automation data  to be prefixed with 'New '
                    // The number does not indicate future designation for the disk & is just used
                    // for logging in the automation process.
                    $hdd->name = 'New disk ' . ++$newDisksCount;
                }

                if ($hdd->capacity < $minHdd) {
                    $message = "HDD";
                    if (!empty($hdd->uuid)) {
                        $message .= " '" . $hdd->uuid . "'";
                    }
                    $message .= " value must be {$minHdd}GB or larger";
                    throw new Exceptions\ForbiddenException($message);
                }

                if ($virtualMachine->inSharedEnvironment() && $hdd->capacity > VirtualMachine::MAX_HDD) {
                    $message = 'HDD';
                    if (!empty($hdd->uuid)) {
                        $message .= " '" . $hdd->uuid . "'";
                    }
                    $message .= ' value must be ' . VirtualMachine::MAX_HDD . 'GB or smaller';
                    throw new Exceptions\ForbiddenException($message);
                }

                $totalCapacity += $hdd->capacity;

                $hdd->state = 'present'; // For when we update the automation
                $automationData['hdd'][$hdd->name] = $hdd;
                $resizeRequired = true;
            }
        }

        // todo add MAX_HDD_COUNT check?

        // Fire off automation request
        if ($resizeRequired) {
            $unchangedDisks = $existingDisks;
            if (!empty($automationData['hdd'])) {
                // Add any unspecified disks to our automation data as we want to send the complete required Storage state
                $unchangedDisks = array_diff_key(
                    $existingDisks,
                    array_flip(array_column($automationData['hdd'], 'uuid'))
                );
            }

            foreach ($unchangedDisks as $disk) {
                $diskData = new \stdClass();
                $diskData->name = $disk->name;
                $diskData->capacity = $disk->capacity;
                $diskData->uuid = $disk->uuid;
                $diskData->state = 'present';
                $automationData['hdd'][$disk->name] = $diskData;
                $totalCapacity += $diskData->capacity;
            }

            $maxCapacity = $maxHdd;
            if ($virtualMachine->inSharedEnvironment()) {
                $maxCapacity = VirtualMachine::MAX_HDD * VirtualMachine::MAX_HDD_COUNT;
            }


            if ($totalCapacity > $maxCapacity) {
                $overprovision = ($totalCapacity - $maxCapacity);
                throw new Exceptions\ForbiddenException(
                    'HDD capacity for virtual machine over-provisioned by ' . $overprovision . 'GB.'
                    . ' Total HDD capacity must be ' . $maxCapacity . 'GB or less.'
                );
            }

            (new ResizeCheck($virtualMachine))->validate();

            try {
                $intapiService->automationRequest(
                    'resize_vm',
                    'server',
                    $virtualMachine->getKey(),
                    $automationData,
                    !empty($virtualMachine->solution) ? 'ecloud_ucs_' . $virtualMachine->solution->pod->getKey() : null,
                    $request->user()->userId(),
                    $request->user()->type()
                );
            } catch (IntapiServiceException $exception) {
                throw new ServiceUnavailableException('Unable to schedule virtual machine changes');
            }

            $virtualMachine->servers_status = Status::RESIZING;
            $virtualMachine->save();

            Log::info(
                'VirtualMachine Resized',
                [
                    'id' => $virtualMachine->getKey(),
                    'type' => $virtualMachine->type(),

                    'kong_request_id' => $request->header('Request-ID'),
                    'kong_consumer_custom_id' => $request->header('X-consumer-custom-id'),
                ]
            );
        }

        return $this->respondEmpty(($resizeRequired) ? 202 : 200);
    }

    /**
     * Extract contracted CPU value from Trigger
     * @param Trigger $trigger
     * @return int
     */
    protected function extractContractTriggerCPUValue(Trigger $trigger)
    {
        preg_match("/\sCPU: ([0-9]+)\s/", $trigger->trigger_description, $regex_matches);
        return intval($regex_matches[1]);
    }

    /**
     * Extract contracted RAM value from Trigger
     * @param Trigger $trigger
     * @return int
     */
    protected function extractContractTriggerRAMValue(Trigger $trigger)
    {
        preg_match("/\sRAM: ([0-9]+)GB\s/", $trigger->trigger_description, $regex_matches);
        return intval($regex_matches[1]);
    }

    /**
     * Extract contracted HDD value from Trigger
     * @param Trigger $trigger
     * @return int
     */
    protected function extractContractTriggerHDDValue(Trigger $trigger)
    {
        preg_match("/\sHDD: ([0-9]+)GB\s/", $trigger->trigger_description, $regex_matches);
        return intval($regex_matches[1]);
    }

    /**
     * Clone VM to template
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws DatastoreInsufficientSpaceException
     * @throws DatastoreNotFoundException
     * @throws Exceptions\ForbiddenException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\UnprocessableEntityException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws Exceptions\DatabaseException
     */
    public function cloneToTemplate(Request $request, IntapiService $intapiService, $vmId)
    {
        $rules = [
            'template_name' => ['required', 'regex:/' . TemplateController::TEMPLATE_NAME_FORMAT_REGEX . '/'],
            'template_type' => ['nullable', 'string'], // 'pod' | 'solution' | null (solution)
        ];

        $this->validate($request, $rules);

        $this->validateVirtualMachineId($request, $vmId);

        //Load the VM
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->type() == 'GPU') {
            throw new Exceptions\ForbiddenException(
                $virtualMachine->type() . ' VM cloning is currently disabled'
            );
        }

        if ($virtualMachine->type() != 'Public') {
            // Check if the solution can modify resources
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        if ($virtualMachine->servers_status != 'Complete') {
            throw new Exceptions\UnprocessableEntityException(
                'Unable to clone vm while status is: ' . $virtualMachine->servers_status
            );
        }

        // Clone to Pod template
        if ($request->has('template_type') && $request->input('template_type') == 'pod') {
            if (!$this->isAdmin) {
                throw new Exceptions\ForbiddenException();
            }

            try {
                $templateDatastore = $virtualMachine->pod->ucs_datacentre_template_datastore;
                if (empty($templateDatastore)) {
                    throw new \Exception('Unable to load VM template datastore: No template datastore defined');
                }
            } catch (\Exception $exception) {
                throw new DatastoreNotFoundException('Unable to load VM template datastore');
            }

            $datastore = DatastoreController::getDatastoreQuery($request)
                ->where('reseller_lun_name', '=', $templateDatastore)  //MCS_PX_VV_999999_DATA_03
                ->first();

            if (!$datastore) {
                throw new DatastoreNotFoundException('Unable to load VM template datastore record.');
            }

            try {
                $existingTemplate = PodTemplate::withFriendlyName(
                    $virtualMachine->pod,
                    $request->input('template_name')
                );
            } catch (TemplateNotFoundException $exception) {
                // Do nothing
            }

            if (!empty($existingTemplate)) {
                throw new Exceptions\UnprocessableEntityException('A template with that name already exists');
            }

            try {
                $datastoreUsage = $datastore->getVmwareUsage();
            } catch (\Exception $exception) {
                throw new ServiceUnavailableException('Unable to determine available datastore space');
            }

            // Check available space
            if ($datastoreUsage->available < $virtualMachine->servers_hdd) {
                throw new DatastoreInsufficientSpaceException(
                    'Datastore has insufficient space, only ' . $datastoreUsage->available . 'GB remaining'
                );
            }

            $templateType = 'system';
        } else {
            // Clone to Solution template
            $templateName = urldecode($request->input('template_name'));

            try {
                $existingTemplate = SolutionTemplate::withName($virtualMachine->solution, $templateName);
            } catch (TemplateNotFoundException $exception) {
                // Do nothing
            }

            if (!empty($existingTemplate)) {
                throw new Exceptions\UnprocessableEntityException('A template with that name already exists');
            }

            // Load the datastores for the Solution
            try {
                $solutionDatastores = $virtualMachine->solution->datastores();
            } catch (\Exception $exception) {
                throw new ServiceUnavailableException('Failed to load solution datastores');
            }

            if (empty($solutionDatastores)) {
                throw new Exceptions\NotFoundException('No datastores were found for this solution');
            }

            // Filter datastores to ones with enough free space
            $datastores = array_filter(
                $solutionDatastores,
                function ($datastore) use ($virtualMachine) {
                    try {
                        $datastoreUsage = $datastore->getVmwareUsage();
                    } catch (\Exception $exception) {
                        throw new ServiceUnavailableException('Unable to determine available datastore space');
                    }

                    return (
                        $datastore->reseller_lun_status == 'Completed'
                        &&
                        $datastore->reseller_lun_lun_type == 'DATA'
                        &&
                        $datastoreUsage->available >= $virtualMachine->servers_hdd
                    );
                }
            );

            if (empty($datastores)) {
                throw new DatastoreInsufficientSpaceException('No datastore available with required space');
            }

            if (count($datastores) > 1) {
                shuffle($datastores);
            }

            $datastore = array_shift($datastores);

            $templateType = 'solution';
        }

        $automationData = [
            'template_name' => $templateName,
            'template_type' => $templateType,
            'datastore_name' => $datastore->reseller_lun_name
        ];


        // Fire off automation request
        try {
            $intapiService->automationRequest(
                'create_template_from_vm',
                'server',
                $virtualMachine->getKey(),
                $automationData,
                !empty($virtualMachine->solution) ? 'ecloud_ucs_' . $virtualMachine->solution->pod->getKey() : null,
                $request->user()->userId(),
                $request->user()->type()
            );
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule virtual machine changes');
        }

        $virtualMachine->servers_status = Status::CLONING_TO_TEMPLATE;
        if (!$virtualMachine->save()) {
            throw new Exceptions\DatabaseException('Failed to update virtual machine status');
        }

        return $this->respondEmpty(202);
    }

    /**
     * Encrypt an existing virtual machine
     * @param Request $request
     * @param IntapiService $intapiService
     * @param AccountsService $accountsService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws EncryptionServiceNotEnabledException
     * @throws Exceptions\BadRequestException
     * @throws Exceptions\NotFoundException
     * @throws InsufficientCreditsException
     * @throws ServiceResponseException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function encrypt(
        Request $request,
        IntapiService $intapiService,
        AccountsService $accountsService,
        $vmId
    ) {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->type() == 'Public') {
            throw new EncryptionServiceNotEnabledException(
                'Encryption service is not available for eCloud Public at this time.'
            );
        }

        (new CanModifyResource($virtualMachine->solution))->validate();

        // Check of the vm is already encrypted
        if ($virtualMachine->servers_encrypted == 'Yes') {
            throw new Exceptions\BadRequestException('The virtual machine is already encrypted.');
        }

        // Check encryption is enabled on the solution
        if (!$virtualMachine->solution->encryptionEnabled()) {
            throw new EncryptionServiceNotEnabledException(
                'Encryption service is not enabled on this solution.'
            );
        }

        $allocateEncryptionCredit = false;

        // PAYG or contract?
        if ($virtualMachine->solution->encryptionBillingType() == EncryptionBillingType::PAYG) {
            // Check there are encryption credits available
            $credits = $accountsService
                ->scopeResellerId($virtualMachine->servers_reseller_id)
                ->getVmEncryptionCredits();
            if (!$credits) {
                throw new ServiceUnavailableException('Unable to load product credits.');
            }
            if ($credits->remaining < 1) {
                throw new InsufficientCreditsException();
            }

            $allocateEncryptionCredit = true;
        }

        // Update the status of the VM
        $virtualMachine->setStatus(Status::ENCRYPTING);

        // Fire off automation request
        try {
            $intapiService->automationRequest(
                'encrypt_vm', //todo: automation needs creating
                'server',
                $virtualMachine->getKey(),
                [],
                !empty($virtualMachine->solution) ? 'ecloud_ucs_' . $virtualMachine->solution->pod->getKey() : null,
                $request->user()->userId(),
                $request->user()->type()
            );

            $intapiData = $intapiService->getResponseData();
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule virtual machine changes');
        }

        if (!$intapiData->result) {
            $error_msg = $intapiService->getFriendlyError(
                is_array($intapiData->errorset->error) ?
                    end($intapiData->errorset->error) :
                    $intapiData->errorset->error
            );

            throw new ServiceResponseException($error_msg);
        }
        $headers = [];
        if ($request->user()->isAdmin()) {
            $headers = [
                'X-AutomationRequestId' => $intapiData->automation_request->id
            ];
        }

        // Allocate the credit
        if ($allocateEncryptionCredit) {
            $result = $accountsService
                ->scopeResellerId($virtualMachine->servers_reseller_id)
                ->assignVmEncryptionCredit($virtualMachine->getKey());

            if (!$result) {
                Log::critical(
                    'Failed to assign credit when encrypting Virtual Machine.',
                    [
                        'id' => $virtualMachine->getKey(),
                        'reseller_id' => $virtualMachine->servers_reseller_id,
                        'credits_available' => $credits->remaining
                    ]
                );
            }
        }

        return $this->respondEmpty(202, $headers);
    }

    /**
     * Decrypt an existing virtual machine
     * @param Request $request
     * @param IntapiService $intapiService
     * @param AccountsService $accountsService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws EncryptionServiceNotEnabledException
     * @throws Exceptions\BadRequestException
     * @throws Exceptions\NotFoundException
     * @throws ServiceUnavailableException
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws ServiceResponseException
     */
    public function decrypt(
        Request $request,
        IntapiService $intapiService,
        AccountsService $accountsService,
        $vmId
    ) {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->type() == 'Public') {
            throw new EncryptionServiceNotEnabledException(
                'Encryption service is not available for eCloud Public at this time.'
            );
        }

        (new CanModifyResource($virtualMachine->solution))->validate();

        // Check of the vm is already encrypted
        if ($virtualMachine->servers_encrypted == 'No') {
            throw new Exceptions\BadRequestException('The virtual machine is already encrypted.');
        }

        // Check encryption is enabled on the solution
        if (!$virtualMachine->solution->encryptionEnabled()) {
            throw new EncryptionServiceNotEnabledException(
                'Encryption service is not enabled on this solution.'
            );
        }

        $deallocateEncryptionCredit = false;

        // PAYG or contract?
        if ($virtualMachine->solution->encryptionBillingType() == EncryptionBillingType::PAYG) {
            $deallocateEncryptionCredit = true;
        }

        // Update the status of the VM
        $virtualMachine->setStatus(Status::DECRYPTING);

        // Fire off automation request
        try {
            $intapiService->automationRequest(
                'decrypt_vm', //todo: automation needs creating
                'server',
                $virtualMachine->getKey(),
                [],
                !empty($virtualMachine->solution) ? 'ecloud_ucs_' . $virtualMachine->solution->pod->getKey() : null,
                $request->user()->userId(),
                $request->user()->type()
            );

            $intapiData = $intapiService->getResponseData();
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule virtual machine changes');
        }

        if (!$intapiData->result) {
            $error_msg = $intapiService->getFriendlyError(
                is_array($intapiData->errorset->error) ?
                    end($intapiData->errorset->error) :
                    $intapiData->errorset->error
            );

            throw new ServiceResponseException($error_msg);
        }
        $headers = [];
        if ($request->user()->isAdmin()) {
            $headers = [
                'X-AutomationRequestId' => $intapiData->automation_request->id
            ];
        }

        // Deallocate encryption credit
        if ($deallocateEncryptionCredit) {
            $result = $accountsService
                ->scopeResellerId($virtualMachine->servers_reseller_id)
                ->refundVmEncryptionCredit($virtualMachine->getKey());

            if (!$result) {
                Log::critical(
                    'Failed to refund encryption credit when decrypting virtual machine',
                    [
                        'id' => $virtualMachine->getKey(),
                        'reseller_id' => $virtualMachine->servers_reseller_id
                    ]
                );
            }
        }

        return $this->respondEmpty(202, $headers);
    }

    /**
     * Hard Power-on or Resume a virtual machine
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     */
    public function powerOn(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        // Check if the solution can modify resources
        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        $result = $this->powerOnVirtualMachine($virtualMachine);
        if (!$result) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        return $this->respondEmpty();
    }

    /**
     * Power on a virtual machine
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     */
    protected function powerOnVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $powerOnResult = $kingpin->powerOnVirtualMachine(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$powerOnResult) {
            return false;
        }

        return true;
    }

    /**
     * Load and configure the Kingpin service for a Virtual Machine
     * @param VirtualMachine $virtualMachine
     * @return \App\Services\Kingpin\V1\KingpinService
     * @throws KingpinException
     */
    protected function loadKingpinService(VirtualMachine $virtualMachine)
    {
        try {
            $kingpin = app()->makeWith(
                'App\Services\Kingpin\V1\KingpinService',
                [$virtualMachine->getPod(), $virtualMachine->type()]
            );
        } catch (\Exception $exception) {
            throw new KingpinException('Unable to connect to Virtual Machine');
        }

        return $kingpin;
    }

    /**
     * Hard Power-off a virtual machine
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     */
    public function powerOff(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        $result = $this->powerOffVirtualMachine($virtualMachine);
        if (!$result) {
            throw new KingpinException('Failed to power off virtual machine');
        }

        return $this->respondEmpty();
    }

    /**
     * Power off a virtual machine
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     */
    protected function powerOffVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $powerOffResult = $kingpin->powerOffVirtualMachine(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$powerOffResult) {
            return false;
        }

        return true;
    }

    /**
     * Gracefully shut down a virtual machine
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function shutdown(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        $result = $this->shutDownVirtualMachine($virtualMachine);
        if (!$result) {
            throw new KingpinException('Failed to shut down virtual machine');
        }

        return $this->respondEmpty();
    }

    /**
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    protected function shutDownVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $shutDownResult = $kingpin->shutDownVirtualMachine(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$shutDownResult) {
            return false;
        }

        $startTime = time();

        do {
            sleep(10);
            $isOnline = $kingpin->checkVMOnline($virtualMachine->getKey(), $virtualMachine->solutionId());
            if ($isOnline === false) {
                return true;
            }
        } while (time() - $startTime < 120);

        throw new ServiceTimeoutException('Timeout waiting for Virtual Machine to power off.');
    }

    /**
     * Restart the virtual machine.
     * Gracefully shutdown from guest, then power on again.
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function restart(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);
        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }
        //Shut down
        $shutDownResult = $this->shutDownVirtualMachine($virtualMachine);
        if (!$shutDownResult) {
            throw new KingpinException('Failed to power down virtual machine');
        }
        //Power up
        $powerOnResult = $this->powerOnVirtualMachine($virtualMachine);
        if (!$powerOnResult) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        return $this->respondEmpty();
    }

    /**
     * Reset the virtual machine.
     * Hard power-off, then power on again.
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     */
    public function reset(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);
        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        //Hard power-off
        $powerOffResult = $this->powerOffVirtualMachine($virtualMachine);
        if (!$powerOffResult) {
            throw new KingpinException('Failed to power off virtual machine');
        }
        //Power up
        $powerOnResult = $this->powerOnVirtualMachine($virtualMachine);
        if (!$powerOnResult) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        return $this->respondEmpty();
    }

    /**
     * Suspend virtual machine (Admin Only)
     * Customers don't need to suspend and resume, it eats resources on the datastore(dumps memory onto disk)
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws Exceptions\ForbiddenException
     */
    public function suspend(Request $request, $vmId)
    {
        if (!$this->isAdmin) {
            throw new Exceptions\ForbiddenException();
        }

        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);
        if ($virtualMachine->type() != 'Public') {
            (new CanModifyResource($virtualMachine->solution))->validate();
        }

        $result = $this->suspendVirtualMachine($virtualMachine);
        if (!$result) {
            $errorMessage = 'Failed to suspend virtual machine';
            throw new KingpinException($errorMessage);
        }

        return $this->respondEmpty();
    }

    /**
     * @param VirtualMachine $virtualMachine
     * @return bool
     * @throws KingpinException
     */
    protected function suspendVirtualMachine(VirtualMachine $virtualMachine)
    {
        $kingpin = $this->loadKingpinService($virtualMachine);

        $powerOnResult = $kingpin->powerSuspend(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );

        if (!$powerOnResult) {
            return false;
        }

        return true;
    }

    /**
     * List all VM's for a Solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     */
    public function getSolutionVMs(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $collection = VirtualMachine::withResellerId($request->user()->resellerId())->withSolutionId($solutionId);

        if (!$this->isAdmin) {
            $collection->where('servers_active', '=', 'y');
        }

        (new QueryTransformer($request))
            ->config(VirtualMachine::class)
            ->transform($collection);

        return $this->respondCollection(
            $request,
            $collection->paginate($this->perPage)
        );
    }

    public function consoleSession(Request $request, $vmId)
    {
        $response = app()->make(AdminClient::class)->devices()->getById($vmId);
        if ($response->managed) {
            abort(403, 'Unable to start session on a managed device');
        }

        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        // hit management resource retrieving the host and ticket values
        $managementResource = $this->loadKingpinService($virtualMachine);
        $response = $managementResource->consoleSession(
            $virtualMachine->getKey(),
            $virtualMachine->solutionId()
        );
        $host = $response['host'] ?? null;
        $ticket = $response['ticket'] ?? null;

        // hit console resource, using the host and ticket from the management resource
        // retrieving the uuid for the console resource session
        $consoleResource = $virtualMachine->pod->resource('console');
        if (!$consoleResource) {
            abort(503);
        }

        $client = new Client([
            'base_uri' => $consoleResource->url,
            'verify' => app()->environment() === 'production',
            'headers' => [
                'X-API-Authentication' => $consoleResource->token,
            ],
        ]);
        $response = $client->post('/session', [
            \GuzzleHttp\RequestOptions::JSON => [
                'host' => $host,
                'ticket' => $ticket,
            ]
        ]);
        $responseJson = json_decode($response->getBody()->getContents());
        $uuid = $responseJson->uuid ?? '';
        if (empty($uuid)) {
            abort(503);
        }

        // respond to the Customer call with the URL containing the session UUID that allows them to connect to the console
        return response()->json([
            'data' => [
                'url' => $consoleResource->console_url . '/?title=id' . $virtualMachine->getKey() . '&session=' . $uuid,
            ],
            'meta' => (object)[]
        ]);
    }
}

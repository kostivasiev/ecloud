<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\DatastoreInsufficientSpaceException;
use App\Exceptions\V1\DatastoreNotFoundException;
use App\Exceptions\V1\SolutionNotFoundException;
use App\Models\V1\Pod;
use App\Models\V1\VirtualMachine;
use App\Models\V1\Solution;
use App\Models\V1\Datastore;

use App\Resources\V1\VirtualMachineResource;

use Illuminate\Http\Request;
use UKFast\Api\Exceptions;

use UKFast\DB\Ditto\QueryTransformer;

use App\Kingpin\V1\KingpinService as Kingpin;
use App\Exceptions\V1\KingpinException;

use App\Services\IntapiService;
use App\Exceptions\V1\IntapiServiceException;

use App\Exceptions\V1\ServiceTimeoutException;
use App\Exceptions\V1\ServiceResponseException;
use App\Exceptions\V1\ServiceUnavailableException;

class VirtualMachineController extends BaseController
{
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
     * @param Request $request
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     */
    public function show(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachines = $this->getVirtualMachines(null, [$vmId]);
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
     * @param Request $request
     * @param IntapiService $intapiService
     * @return \Illuminate\Http\Response
     * @throws IntapiServiceException
     * @throws ServiceResponseException
     * @throws SolutionNotFoundException
     * @throws \App\Exceptions\V1\TemplateNotFoundException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request, IntapiService $intapiService)
    {
        // default validation
        $rules = [
            'environment' => ['required'],
            'template' => ['required'],

            'cpu' => ['required', 'integer'],
            'ram' => ['required', 'integer'],
            'hdd' => ['required', 'integer'],
        ];

        // todo public iops

        if (strtolower($request->input('environment')) != 'public') {
            $rules['solution_id'] = ['required', 'integer', 'min:1'];
        }

        if ($request->has('name')) {
            $rules['name'] = [
                'regex:/'.VirtualMachine::NAME_FORMAT_REGEX.'/'
            ];
        }

        if ($request->input('monitoring') === true) {
            $rules['monitoring-contacts'] = 'required';
        }

        // todo validate tags

        $this->validate($request, $rules);


        // environment specific validation
        if (strtolower($request->input('environment')) == 'public') {
            $solution = null;
            $pod = new Pod();
        } else {
            $solution = SolutionController::getSolutionById($request, $request->input('solution_id'));
            $pod = $solution->pod;
        }

        // todo check available resources

        $rules['cpu'] = array_merge($rules['cpu'], [
            'min:'.VirtualMachine::MIN_CPU, 'max:'.VirtualMachine::MAX_CPU
        ]);

        $rules['ram'] = array_merge($rules['ram'], [
            'min:'.VirtualMachine::MIN_RAM, 'max:'.VirtualMachine::MAX_RAM
        ]);

        $rules['hdd'] = array_merge($rules['hdd'], [
            'min:'.VirtualMachine::MIN_HDD, 'max:'.VirtualMachine::MAX_HDD
        ]);


        // todo is multi-vlan
        // todo is multi-site

        $this->validate($request, $rules);

        // check template is valid
        $template = TemplateController::getTemplateByName(
            $request->input('template'),
            $pod,
            $solution
        );

        if ($request->has('computername')) {
            if ($template->platform == 'Linux') {
                $rules['computername'] = [
                    'regex:/'.VirtualMachine::HOSTNAME_FORMAT_REGEX.'/'
                ];
            } elseif ($template->platform == 'Windows') {
                $rules['computername'] = [
                    'regex:/'.VirtualMachine::NETBIOS_FORMAT_REGEX.'/'
                ];
            }
        }


        // todo request is larger than template

        $this->validate($request, $rules);

        // set initial _post data
        $post_data = array(
            'reseller_id'       => $request->user->resellerId,
            'ecloud_type'       => ucfirst(strtolower($request->input('environment'))),
            'ucs_reseller_id'   => $request->input('solution_id'),
            'server_active'     => true,

            'name'              => $request->input('name'),
            'netbios'           => $request->input('computername'),

            'submitted_by_type' => 'API Client',
            'submitted_by_id'   => $request->user->applicationId,
            'launched_by'       => '-5',
        );


        // set template
        $post_data['platform'] = $template->platform;
        $post_data['license'] = $template->license;

        if ($template->type != 'Base') {
            $post_data['template'] = $request->input('template');

            if ($template->type != 'Solution') {
                $post_data['template_type'] = 'System';
            }
        }

        if ($request->has('template_password')) {
            $post_data['template_password'] = $request->input('template_password');
        }


        // set compute
        $post_data['cpus'] = $request->input('cpu');
        $post_data['ram_gb'] = $request->input('ram');


        // set storage
        $post_data['hdd_gb'] = $request->input('hdd');

        if ($request->has('datastore_id')) {
            $post_data['reseller_lun_id'] = $request->input('datastore_id');
        } else {
            // todo find the datastore with the most free storage
        }


        // set networking
        // todo check/set VLAN

        if ($request->has('external_ip_required')) {
            $post_data['external_ip_required'] = $request->input('external_ip_required');
        }

        // set nameservers
        if ($request->has('nameservers')) {
            $post_data['nameservers'] = $request->input('nameservers');
        }

        // site id
            // drs group


        // set support
        if ($request->input('monitoring') === true) {
            $post_data['advanced_support'] = true;
        }

        if ($request->input('monitoring') === true) {
            $post_data['monitoring_enabled'] = true;
            $post_data['monitoring_contacts'] = $request->input('monitoring-contacts');
        }

        // todo remove debugging when ready to retest
//        print_r($post_data);
//        exit;

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
            throw new IntapiServiceException('Failed to create new virtual machine', null, 502);
        }

        if (!$intapiData->result) {
            $error_msg = end($intapiData->errorset);

            if (!$request->user->isAdmin) {
                $error_msg = $intapiService->getFriendlyError($error_msg);
                throw new ServiceResponseException($error_msg);
            }

            throw new IntapiServiceException($error_msg);
        }

        $virtualMachine = new VirtualMachine();
        $virtualMachine->servers_id = $intapiData->data->server_id;
        $virtualMachine->servers_status = $intapiData->data->server_status;

        $headers = [];
        if ($request->user->isAdmin) {
            $headers = [
                'X-AutomationRequestId' => $intapiData->data->automation_request_id
            ];
        }

        return $this->respondSave($request, $virtualMachine, 202, null, $headers);
    }
    /**
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\ForbiddenException
     * @throws ServiceUnavailableException
     */
    public function destroy(Request $request, IntapiService $intapiService, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachines($request->user->resellerId)->find($vmId);

        //cant delete vm if its doing something that requires it to exist
        if (!$virtualMachine->canBeDeleted()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted with status of: ' . $virtualMachine->servers_status
            );
        }

        //server is in contract
        if (!$request->user->isAdmin && $virtualMachine->inContract()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, in contract until ' .
                date('d/m/Y', strtotime($virtualMachine->servers_contract_end_date))
            );
        }

        //server is a managed device
        if (!$request->user->isAdmin && $virtualMachine->isManaged()) {
            throw new Exceptions\ForbiddenException(
                'VM cannot be deleted, device is managed by UKFast'
            );
        }

        //schedule automation
        try {
            $automationRequestId = $intapiService->automationRequest(
                'delete_vm',
                'server',
                $virtualMachine->getKey(),
                [],
                'ecloud_ucs_' . $virtualMachine->pod->getKey(),
                $request->user->applicationId
            );
        } catch (IntapiServiceException $exception) {
            throw new ServiceUnavailableException('Unable to schedule deletion request');
        }

        $virtualMachine->servers_status = 'Pending Deletion';
        if (!$virtualMachine->save()) {
            //Log::critical('');
        }

        $headers = [];
        if ($request->user->isAdmin) {
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
     * @param $vmId
     * @return \Illuminate\Http\Response
     * @throws DatastoreInsufficientSpaceException
     * @throws DatastoreNotFoundException
     * @throws Exceptions\NotFoundException
     * @throws ServiceUnavailableException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function clone(Request $request, IntapiService $intapiService, $vmId)
    {
        //Validation
        $rules = [
            'name' => ['required', 'regex:/' . VirtualMachine::NAME_FORMAT_REGEX . '/'],
            'type' => ['required', 'in:Hybrid'], //Private,Public - Cloning is not currently available for public VM's

        ];

        $this->validateVirtualMachineId($request, $vmId);
        $this->validate($request, $rules);

        //Load the vm to clone
        $virtualMachine = $this->getVirtualMachine($vmId);

        //Load the default datastore and check there's enough space
        //For Hybrid the default is the available datastore with the most free space
        if ($virtualMachine->isDedicated()) {
            $datastore = Datastore::getDefault($virtualMachine->servers_ecloud_ucs_reseller_id, 'dedicated');
        } else {
            $datastore = Datastore::getDefault(null, 'shared');
            if ($datastore instanceof Datastore) {
                $datastore->usage->available = $datastore->reseller_lun_size_gb;
            }
        }

        if (!$datastore instanceof Datastore) {
            throw new DatastoreNotFoundException('Unable to load datastore');
        }

        if ($datastore->usage->available < $virtualMachine->servers_hdd) {
            $message = 'Insufficient free space on selected datastore.' .
                ' Request required ' . $virtualMachine->servers_hdd . 'GB, datastore has '
                . $datastore->usage->available . 'GB remaining';
            throw new DatastoreInsufficientSpaceException($message);
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

        $responseData = $intapiService->getResponseData();
        $automationRequestId = $responseData->data->automation_request_id;

        // Respond with the new machine id
        $headers = [];
        if ($request->user->isAdmin) {
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
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function powerOff(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        $result = $this->shutDownVirtualMachine($virtualMachine);
        if (!$result) {
            $errorMessage = 'Failed to power off virtual machine';
            throw new KingpinException($errorMessage);
        }

        $this->respondEmpty();
    }

    /**
     * Power on a virtual machine
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     */
    public function powerOn(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);

        $result = $this->powerOnVirtualMachine($virtualMachine);
        if (!$result) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        $this->respondEmpty();
    }

    /**
     * Power-cycle the virtual machine - Power off then on again.
     * @param Request $request
     * @param $vmId
     * @throws Exceptions\NotFoundException
     * @throws KingpinException
     * @throws ServiceTimeoutException
     */
    public function powerCycle(Request $request, $vmId)
    {
        $this->validateVirtualMachineId($request, $vmId);
        $virtualMachine = $this->getVirtualMachine($vmId);
        //Power down
        $shutDownResult = $this->shutDownVirtualMachine($virtualMachine);
        if (!$shutDownResult) {
            throw new KingpinException('Failed to power down virtual machine');
        }
        sleep(3);
        //Power up
        $powerOnResult = $this->powerOnVirtualMachine($virtualMachine);
        if (!$powerOnResult) {
            throw new KingpinException('Failed to power on virtual machine');
        }

        $this->respondEmpty();
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
     * Load and configure the Kingpin service for a Virtual Machine
     * @param VirtualMachine $virtualMachine
     * @return mixed
     * @throws KingpinException
     */
    protected function loadKingpinService(VirtualMachine $virtualMachine)
    {
        try {
            $kingpin = app()->makeWith(
                'App\Kingpin\V1\KingpinService',
                [$virtualMachine->getPod(), $virtualMachine->type()]
            );
        } catch (\Exception $exception) {
            throw new KingpinException('Unable to connect to Virtual Machine');
        }

        return $kingpin;
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
        $virtualMachineQuery = $this->getVirtualMachines(null, [$vmId]);
        $VirtualMachine = $virtualMachineQuery->first();
        if (!$VirtualMachine) {
            throw new Exceptions\NotFoundException("The Virtual Machine '$vmId' Not Found");
        }
        return $VirtualMachine;
    }

    /**
     * List VM's
     * For admin list all except when $resellerId is passed in
     * @param null $resellerId
     * @param array $vmIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getVirtualMachines($resellerId = null, $vmIds = [])
    {
        $virtualMachineQuery = VirtualMachine::query();
        if (!empty($vmIds)) {
            $virtualMachineQuery->whereIn('servers_id', $vmIds);
        }
        if ($this->isAdmin) {
            if (!is_null($resellerId)) {
                $virtualMachineQuery->withResellerId($resellerId);
            }
            // Return ALL VM's
            return $virtualMachineQuery;
        }

        $virtualMachineQuery->where('servers_active', '=', 'y');

        //For non-admin filter on reseller ID
        return $virtualMachineQuery->withResellerId($this->resellerId);
    }

    /**
     * Validates the solution id
     * @param Request $request
     * @param $vmId
     * @return void
     */
    protected function validateVirtualMachineId(&$request, $vmId)
    {
        $request['vm_id'] = $vmId;
        $this->validate($request, ['vm_id' => 'required|integer']);
    }

    /**
     * List all VM's for a Solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     */
    public function getSolutionVMs(Request $request, $solutionId)
    {
        $collection = VirtualMachine::withResellerId($request->user->resellerId)->withSolutionId($solutionId);

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
}

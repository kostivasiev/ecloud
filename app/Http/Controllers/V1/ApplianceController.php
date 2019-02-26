<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
use App\Exceptions\V1\TemplateNotFoundException;
use App\Models\V1\AppliancePodAvailability;
use App\Rules\V1\IsValidUuid;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Exceptions\ForbiddenException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Appliance;

use App\Models\V1\ApplianceVersion;

class ApplianceController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * List all Appliances
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getApplianceQuery($request);

        (new QueryTransformer($request))
            ->config(Appliance::class)
            ->transform($collectionQuery);

        $appliances = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $appliances,
            200,
            null,
            [],
            ($this->isAdmin) ? null : Appliance::VISIBLE_SCOPE_RESELLER
        );
    }

    /**
     * Get a singe Appliance resource
     *
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     */
    public function show(Request $request, $applianceId)
    {
        $request['id'] = $applianceId;
        $this->validate($request, ['id' => [new IsValidUuid()]]);

        return $this->respondItem(
            $request,
            static::getApplianceById($request, $applianceId),
            200,
            null,
            [],
            ($this->isAdmin) ? null : Appliance::VISIBLE_SCOPE_RESELLER
        );
    }


    /**
     * Create appliance resource
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResourceException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidResponseException
     * @throws \UKFast\Api\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can publish appliances at this time.');
        }

        $this->validate($request, Appliance::$rules);

        //Receive the user data
        $appliance = $this->receiveItem($request, Appliance::class);

        // Default the publisher to UKFast
        if (!$request->has('publisher')) {
            $appliance->resource->publisher = 'UKFast';
        }

        // Save the record
        if (!$appliance->save()) {
            throw new DatabaseException('Failed to save appliance record.');
        }

        return $this->respondSave(
            $request,
            $appliance,
            201
        );
    }

    /**
     * Update an application resource
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws ApplianceNotFoundException
     */
    public function update(Request $request, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can update appliances at this time.');
        }

        // Validate the the appliance exists:
        static::getApplianceById($request, $applianceId);

        $rules = Appliance::$rules;
        $rules = array_merge(
            $rules,
            [
                'name' => ['nullable', 'max:255'],
                'id' => [new IsValidUuid()]
            ]
        );

        $request['id'] = $applianceId;
        $this->validate($request, $rules);

        $appliance = $this->receiveItem($request, Appliance::class);

        if (!$appliance->resource->save()) {
            throw new DatabaseException('Could not update appliance');
        }

        return $this->respondEmpty();
    }


    /**
     * Return a list of versions for the appliance
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws ForbiddenException
     */
    public function versions(Request $request, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can update appliances at this time.');
        }

        $appliance = static::getApplianceById($request, $applianceId);

        $collectionQuery = ApplianceVersionController::getApplianceVersionQuery($request);
        $collectionQuery->where('appliance_version_appliance_id', '=', $appliance->id);
        $collectionQuery->orderBy('appliance_version_version', 'DESC');

        (new QueryTransformer($request))
            ->config(ApplianceVersion::class)
            ->transform($collectionQuery);

        $applianceVersions = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $applianceVersions
        );
    }


    /**
     * Return the parameters for the latest version of the appliance
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws \App\Exceptions\V1\ApplianceVersionNotFoundException
     */
    public function latestVersionParameters(Request $request, $applianceId)
    {
        $appliance = static::getApplianceById($request, $applianceId);

        $applianceVersion = $appliance->getLatestVersion();

        $applianceVersionController = new ApplianceVersionController($request);

        return $applianceVersionController->versionParameters($request, $applianceVersion->uuid);
    }


    /**
     * Return the latest appliance version
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws \App\Exceptions\V1\ApplianceVersionNotFoundException
     */
    public function latestVersion(Request $request, $applianceId)
    {
        $appliance = static::getApplianceById($request, $applianceId);

        $applianceVersion = $appliance->getLatestVersion();

        $applianceVersionController = new ApplianceVersionController($request);

        return $applianceVersionController->show($request, $applianceVersion->uuid);
    }


    /**
     * List the appliances available in a pod
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     */
    public function podAvailability(Request $request, $podId)
    {
        $applianceQuery = static::getApplianceQuery($request);
        $applianceQuery->join(
            'appliance_pod_availability',
            'appliance.appliance_id',
            'appliance_pod_availability.appliance_pod_availability_appliance_id'
        )
            ->where('appliance_pod_availability_ucs_datacentre_id', '=', $podId);

        if (!$this->isAdmin) {
            $applianceQuery->join(
                'reseller.ucs_datacentre',
                'appliance_pod_availability_ucs_datacentre_id',
                'ucs_datacentre.ucs_datacentre_id'
            )->where('ucs_datacentre_oneclick_enabled', '=', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(Appliance::class)
            ->transform($applianceQuery);

        $appliances = $applianceQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $appliances,
            200,
            null,
            [],
            ($this->isAdmin) ? null : Appliance::VISIBLE_SCOPE_RESELLER
        );
    }


    /**
     * Add an appliance to a pod
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws TemplateNotFoundException
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function addToPod(Request $request, $podId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can update appliances at this time.');
        }

        $this->validate($request, ['appliance_id' => ['required', new IsValidUuid()]]);

        // Validate the Pod
        $pod = PodController::getPodById($request, $podId);

        $appliance = static::getApplianceById($request, $request->input('appliance_id'));

        // Validate that the VM template associated with the Appliance is on the Pod
        $latestVersion = $appliance->getLatestVersion();
        try {
            $template = TemplateController::getTemplateByName($latestVersion->vm_template, $pod);
        } catch (TemplateNotFoundException $exception) {
            throw new TemplateNotFoundException(
                'The VM template \'' . $latestVersion->vm_template
                . '\' associated with this Application\'s latest version was not found on this Pod'
            );
        }

        $row = new AppliancePodAvailability();
        $row->appliance_id = $appliance->id;
        $row->ucs_datacentre_id = $podId;
        try {
            $row->save();
        } catch (\Exception $exception) {
            $message = 'Unable to add Appliance to pod';
            if ($exception->getCode() == 23000) {
                $message .= ': The Appliance is already in this Pod.';
            }
            throw new DatabaseException($message);
        }

        return $this->respondEmpty();
    }


    /**
     * Load an appliance by UUID
     * @param Request $request
     * @param $applianceId
     * @return mixed
     * @throws ApplianceNotFoundException
     */
    public static function getApplianceById(Request $request, $applianceId)
    {
        $appliance = static::getApplianceQuery($request)->find($applianceId);

        if (is_null($appliance)) {
            throw new ApplianceNotFoundException("Appliance with ID '$applianceId' was not found", 'id');
        }

        return $appliance;
    }

    /**
     * Get appliances query builder
     * @param $request
     * @return mixed
     */
    public static function getApplianceQuery($request)
    {
        $applianceQuery = Appliance::query();

        if ($request->user->resellerId != 0) {
            $applianceQuery->where('appliance_active', 'Yes');
        }

        $applianceQuery->whereNull('appliance_deleted_at');

        return $applianceQuery;
    }
}

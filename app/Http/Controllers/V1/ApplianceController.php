<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
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
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function addToPod(Request $request, $podId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can update appliances at this time.');
        }

        $this->validate($request, ['appliance_id' => ['required', new IsValidUuid()]]);

        // Validate the Pod
        PodController::getPodById($request, $podId);

        $appliance = static::getApplianceById($request, $request->input('appliance_id'));

        $row = new AppliancePodAvailability();
        $row->appliance_id = $appliance->id;
        $row->ucs_datacentre_id = $podId;
        try {
            $row->save();
        } catch (\Exception $exception) {
            throw new DatabaseException('Unable to add Appliance to pod');
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

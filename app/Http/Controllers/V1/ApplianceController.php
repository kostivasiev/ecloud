<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
use App\Exceptions\V1\TemplateNotFoundException;
use App\Models\V1\Appliance;
use App\Models\V1\Appliance\Version\Data;
use App\Models\V1\AppliancePodAvailability;
use App\Models\V1\ApplianceVersion;
use App\Models\V1\Pod;
use App\Rules\V1\IsValidUuid;
use Illuminate\Http\Request;
use Log;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Exceptions\ForbiddenException;
use App\Services\V1\Resource\Traits\RequestHelper;
use App\Services\V1\Resource\Traits\ResponseHelper;
use UKFast\DB\Ditto\QueryTransformer;

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
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResourceException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidResponseException
     * @throws \App\Services\V1\Resource\Exceptions\InvalidRouteException
     */
    public function create(Request $request)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
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
     * @return \Illuminate\Http\JsonResponse
     * @throws DatabaseException
     * @throws ForbiddenException
     * @throws ApplianceNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
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

        return $this->responseIdMeta($request, $applianceId, 200);
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
            throw new ForbiddenException();
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
     * Delete an appliance.
     *
     * Soft deletes the appliance, and cascades to delete any appliance versions and parameters.
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws DatabaseException
     * @throws ForbiddenException
     */
    public function delete(Request $request, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }

        $appliance = static::getApplianceById($request, $applianceId);
        try {
            $appliance->delete();
        } catch (\Exception $exception) {
            throw new DatabaseException('Failed to delete the appliance');
        }

        return $this->respondEmpty();
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
     * Return the data for the latest version of the appliance
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     */
    public function latestVersionData(Request $request, $applianceId)
    {
        $appliance = static::getApplianceById($request, $applianceId);
        return response()->json([
            'data' => Data::select('key', 'value')->where([
                ['appliance_version_uuid', '=', $appliance->getLatestVersion()->appliance_version_uuid],
            ])->get()->all(),
            'meta' => [],
        ]);
    }


    /**
     * Return the latest appliance version
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     */
    public function latestVersion(Request $request, $applianceId)
    {
        $appliance = static::getApplianceById($request, $applianceId);

        $applianceVersion = $appliance->getLatestVersion();

        return $this->respondItem(
            $request,
            $applianceVersion,
            200,
            null,
            [],
            ($this->isAdmin) ? null : ApplianceVersion::VISIBLE_SCOPE_RESELLER
        );
    }


    /**
     * List the appliances available in a pod
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     */
    public function podAvailability(Request $request, $podId)
    {
        // Get the one-click enabled pods this way to avoid cross database query
        $oneClickPods = Pod::select('ucs_datacentre_id')
            ->where('ucs_datacentre_oneclick_enabled', '=', 'Yes')
            ->pluck('ucs_datacentre_id')
            ->toArray();

        $applianceQuery = static::getApplianceQuery($request);
        $applianceQuery->join(
            'appliance_pod_availability',
            'appliance.appliance_id',
            'appliance_pod_availability.appliance_pod_availability_appliance_id'
        )
            ->where('appliance_pod_availability_ucs_datacentre_id', '=', $podId);

        if (!$this->isAdmin) {
            // Limit to one-click enabled pods
            $applianceQuery->whereIn('appliance_pod_availability_ucs_datacentre_id', $oneClickPods);
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
     * List the Pods that an appliance is available on
     * @param Request $request
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     */
    public function pods(Request $request, $applianceId)
    {
        $appliance = static::getApplianceById($request, $applianceId);

        $podIds = AppliancePodAvailability::select('appliance_pod_availability_ucs_datacentre_id')->where(
            'appliance_pod_availability_appliance_id',
            '=',
            $appliance->appliance_id
        )->get()->toArray();

        // Get the one-click enabled pods this way to avoid cross database query
        $podsQuery = Pod::query()->whereIn('ucs_datacentre_id', $podIds);

        if (!$this->isAdmin) {
            // Limit to one-click enabled pods
            $podsQuery->where('ucs_datacentre_oneclick_enabled', '=', 'Yes');
        }

        (new QueryTransformer($request))
            ->config(Pod::class)
            ->transform($podsQuery);

        $pods = $podsQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $pods
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
            throw new ForbiddenException();
        }

        $this->validate($request, ['appliance_id' => ['required', new IsValidUuid()]]);

        // Validate the Pod
        $pod = PodController::getPodById($request, $podId);

        $appliance = static::getApplianceById($request, $request->input('appliance_id'));

        $latestVersion = $appliance->getLatestVersion();

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
     * @param Request $request
     * @param $podId
     * @param $applianceId
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     * @throws DatabaseException
     * @throws ForbiddenException
     */
    public function removeFromPod(Request $request, $podId, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException();
        }

        $request['appliance_id'] = $applianceId;
        $this->validate($request, ['appliance_id' => ['required', new IsValidUuid()]]);

        $appliance = static::getApplianceById($request, $applianceId);

        $podQry = AppliancePodAvailability::query();
        $podQry->where('appliance_pod_availability_appliance_id', '=', $appliance->id);
        $podQry->where('appliance_pod_availability_ucs_datacentre_id', '=', $podId);

        if ($podQry->count() < 1) {
            throw new ApplianceNotFoundException('The appliance was not found in the Pod');
        }

        try {
            AppliancePodAvailability::destroy($podQry->first()->id);
        } catch (\Exception $exception) {
            throw new DatabaseException('Unable to remove the appliance from the pod');
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

        if ($request->user()->isScoped()) {
            $applianceQuery->where('appliance_active', 'Yes');
            $applianceQuery->where('appliance_is_public', 'Yes');
        }

        return $applianceQuery;
    }
}

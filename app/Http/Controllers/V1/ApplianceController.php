<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
use App\Rules\V1\IsValidUuid;
use UKFast\Api\Exceptions\DatabaseException;
use UKFast\Api\Exceptions\ForbiddenException;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Appliance;

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

        //TODO: We should add the appliance version (latest active version ) to the data

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
     */
    public function update(Request $request, $applianceId)
    {
        if (!$this->isAdmin) {
            throw new ForbiddenException('Only UKFast can update appliances at this time.');
        }

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

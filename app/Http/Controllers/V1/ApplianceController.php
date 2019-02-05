<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
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
     * @param $applianceUuid
     * @return \Illuminate\Http\Response
     * @throws ApplianceNotFoundException
     */
    public function show(Request $request, $applianceUuid)
    {
        return $this->respondItem(
            $request,
            static::getApplianceByUuid($request, $applianceUuid),
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

        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'logo_uri' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'documentation_uri' => ['nullable', 'string'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean']
        ]);

        // Save the record
        $appliance = $this->receiveItem($request, Appliance::class);

        if (!$appliance->save()) {
            throw new DatabaseException('Failed to save appliance record.');
        }

        return $this->respondSave(
            $request,
            $appliance,
            201,
            null,
            [],
            [],
            $request->path() . '/' . $appliance->getUuid()
        );
    }


    /**
     * Load an appliance by UUID
     * @param Request $request
     * @param $uuid
     * @return mixed
     * @throws ApplianceNotFoundException
     */
    public static function getApplianceByUuid(Request $request, $uuid)
    {
        $appliance = static::getApplianceQuery($request)->withUuid($uuid)->first();

        if (is_null($appliance)) {
            throw new ApplianceNotFoundException("Appliance with ID '$uuid' was not found", 'id');
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

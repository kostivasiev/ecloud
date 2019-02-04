<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\ApplianceNotFoundException;
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
        $collectionQuery = static::getApplianceQuery();

        (new QueryTransformer($request))
            ->config(Appliance::class)
            ->transform($collectionQuery);

        $appliances = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $appliances
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
            static::getApplianceByUuid($applianceUuid)
        );
    }

    /**
     * Load an appliance by UUID
     * @param $uuid
     * @return mixed
     * @throws ApplianceNotFoundException
     */
    public static function getApplianceByUuid($uuid)
    {
        $appliance = static::getApplianceQuery()->withUuid($uuid)->first();

        if (is_null($appliance)) {
            throw new ApplianceNotFoundException("Appliance with ID '$uuid' was not found", 'id');
        }

        return $appliance;
    }
    /**
     * Get appliances query builder
     * @return mixed
     */
    public static function getApplianceQuery()
    {
        $applianceQuery = Appliance::query();
        $applianceQuery->where('appliance_active', 'Yes');
        $applianceQuery->whereNull('appliance_deleted_at');

        return $applianceQuery;
    }

}

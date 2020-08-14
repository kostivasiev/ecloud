<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateAvailabilityZoneRequest;
use App\Http\Requests\V2\UpdateAvailabilityZoneRequest;
use App\Resources\V2\AvailabilityZoneResource;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class AvailabilityZoneController
 * @package App\Http\Controllers\V2
 */
class AvailabilityZoneController extends BaseController
{
    /**
     * Get availability zones collection
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = AvailabilityZone::query();

        $queryTransformer->config(AvailabilityZone::class)
            ->transform($collection);

        return AvailabilityZoneResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \App\Resources\V2\AvailabilityZoneResource
     */
    public function show(Request $request, string $zoneId)
    {
        return new AvailabilityZoneResource(
            AvailabilityZone::findOrFail($zoneId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateAvailabilityZoneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateAvailabilityZoneRequest $request)
    {
        $availabilityZone = new AvailabilityZone($request->only([
            'code', 'name', 'datacentre_site_id', 'is_public', 'region_id', 'nsx_manager_endpoint',
        ]));
        $availabilityZone->save();
        $availabilityZone->refresh();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateAvailabilityZoneRequest $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAvailabilityZoneRequest $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        $availabilityZone->fill($request->only([
            'code', 'name', 'datacentre_site_id', 'is_public', 'region_id', 'nsx_manager_endpoint'
        ]));
        $availabilityZone->save();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        $availabilityZone->delete();
        return response()->json([], 204);
    }

    /**
     * Associate a router with an availability_zone
     * @param string $zoneId
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function routersCreate(string $zoneId, string $routerUuid)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        $router = Router::findOrFail($routerUuid);
        $availabilityZone->routers()->attach($router->id);
        return response()->json([], 204);
    }

    /**
     * Disassociate a route with an availability_zone
     * @param string $zoneId
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function routersDestroy(string $zoneId, string $routerUuid)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        $router = Router::findOrFail($routerUuid);
        $availabilityZone->routers()->detach($router->id);
        return response()->json([], 204);
    }
}

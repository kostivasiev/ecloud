<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\AvailabilityZones\AfterCreateEvent;
use App\Events\V2\AvailabilityZones\AfterDeleteEvent;
use App\Events\V2\AvailabilityZones\AfterUpdateEvent;
use App\Events\V2\AvailabilityZones\BeforeCreateEvent;
use App\Events\V2\AvailabilityZones\BeforeDeleteEvent;
use App\Events\V2\AvailabilityZones\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateAvailabilityZonesRequest;
use App\Http\Requests\V2\UpdateAvailabilityZonesRequest;
use App\Resources\V2\AvailabilityZonesResource;
use App\Models\V2\AvailabilityZones;
use App\Models\V2\Routers;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class AvailabilityZonesController
 * @package App\Http\Controllers\V2
 */
class AvailabilityZonesController extends BaseController
{
    /**
     * Get availability zones collection
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = AvailabilityZones::query();

        $queryTransformer->config(AvailabilityZones::class)
            ->transform($collection);

        return AvailabilityZonesResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \App\Http\Resources\V2\AvailabilityZonesResource
     */
    public function show(Request $request, string $zoneId)
    {
        return new AvailabilityZonesResource(
            AvailabilityZones::findOrFail($zoneId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateAvailabilityZonesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateAvailabilityZonesRequest $request)
    {
        event(new BeforeCreateEvent());
        $availabilityZone = new AvailabilityZones($request->only([
            'code', 'name', 'site_id', 'nsx_manager_endpoint',
        ]));
        $availabilityZone->save();
        $availabilityZone->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateAvailabilityZonesRequest $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAvailabilityZonesRequest $request, string $zoneId)
    {
        event(new BeforeUpdateEvent());
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $availabilityZone->fill($request->only([
            'code', 'name', 'site_id', 'nsx_manager_endpoint',
        ]));
        $availabilityZone->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $zoneId)
    {
        event(new BeforeDeleteEvent());
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $availabilityZone->delete();
        event(new AfterDeleteEvent());
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
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $router = Routers::findOrFail($routerUuid);
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
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $router = Routers::findOrFail($routerUuid);
        $availabilityZone->routers()->detach($router->id);
        return response()->json([], 204);
    }
}

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
use App\Models\V2\AvailabilityZones;
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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = AvailabilityZones::query();

        (new QueryTransformer($request))
            ->config(AvailabilityZones::class)
            ->transform($collectionQuery);

        $availabilityZones = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $availabilityZones,
            200
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $zoneId)
    {
        return $this->respondItem(
            $request,
            AvailabilityZones::findOrFail($zoneId),
            200
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
}

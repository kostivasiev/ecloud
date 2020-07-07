<?php

namespace App\Http\Controllers\V2;

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
        $availabilityZone = new AvailabilityZones($request->only([
            'code', 'name', 'site_id',
        ]));
        $availabilityZone->save();
        $availabilityZone->refresh();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 201);
    }

    public function update(UpdateAvailabilityZonesRequest $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $availabilityZone->replace($request->only([
            'code', 'name', 'site_id',
        ]));
        $availabilityZone->save();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 202);
    }

    public function destroy(Request $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZones::findOrFail($zoneId);
        $availabilityZone->delete();
        return response()->json([], 204);
    }
}

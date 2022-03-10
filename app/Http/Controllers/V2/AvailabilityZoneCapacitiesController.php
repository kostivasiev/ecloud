<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AvailabilityZoneCapacity\Create;
use App\Http\Requests\V2\AvailabilityZoneCapacity\Update;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Resources\V2\AvailabilityZoneCapacityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityZoneCapacitiesController extends BaseController
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $collection = AvailabilityZoneCapacity::query();

        return AvailabilityZoneCapacityResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param Request $request
     * @param string $capacityId
     * @return AvailabilityZoneCapacityResource
     */
    public function show(Request $request, string $capacityId)
    {
        return new AvailabilityZoneCapacityResource(
            AvailabilityZoneCapacity::findOrFail($capacityId)
        );
    }

    /**
     * @param Create $request
     * @return JsonResponse
     */
    public function create(Create $request)
    {
        $availabilityZoneCapacity = new AvailabilityZoneCapacity($request->only([
            'availability_zone_id',
            'type',
            'alert_warning',
            'alert_critical',
            'max',
        ]));
        $availabilityZoneCapacity->save();
        return $this->responseIdMeta($request, $availabilityZoneCapacity->id, 201);
    }

    /**
     * @param Update $request
     * @param string $capacityId
     * @return JsonResponse
     */
    public function update(Update $request, string $capacityId)
    {
        $availabilityZoneCapacity = AvailabilityZoneCapacity::findOrFail($capacityId);
        $availabilityZoneCapacity->fill($request->only([
            'availability_zone_id',
            'type',
            'alert_warning',
            'alert_critical',
            'max',
        ]));
        $availabilityZoneCapacity->save();
        return $this->responseIdMeta($request, $availabilityZoneCapacity->id, 200);
    }

    public function destroy(Request $request, string $capacityId)
    {
        AvailabilityZoneCapacity::findOrFail($capacityId)
            ->delete();
        return response('', 204);
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Vpc\AfterCreateEvent;
use App\Events\V2\Vpc\AfterDeleteEvent;
use App\Events\V2\Vpc\AfterUpdateEvent;
use App\Events\V2\Vpc\BeforeCreateEvent;
use App\Events\V2\Vpc\BeforeDeleteEvent;
use App\Events\V2\Vpc\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateRegionRequest;
use App\Http\Requests\V2\CreateVpcRequest;
use App\Http\Requests\V2\UpdateRegionRequest;
use App\Http\Requests\V2\UpdateVpcRequest;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\RegionResource;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class RegionController
 * @package App\Http\Controllers\V2
 */
class RegionController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Region::query();
        (new QueryTransformer($request))
            ->config(Region::class)
            ->transform($collection);

        return RegionResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $regionId
     * @return RegionResource
     */
    public function show(Request $request, string $regionId)
    {
        return new RegionResource(
            Region::findOrFail($regionId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateRegionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRegionRequest $request)
    {
        $region = new Region($request->only(['name']));
        $region->save();
        return $this->responseIdMeta($request, $region->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateRegionRequest $request
     * @param string $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRegionRequest $request, string $regionId)
    {
        $region = Region::findOrFail($regionId);
        $region->fill($request->only(['name']))->save();
        return $this->responseIdMeta($request, $region->getKey(), 200);
    }


    /**
     * @param \Illuminate\Http\Request $request
     * @param string $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $regionId)
    {
        Region::findOrFail($regionId)->delete();
        return response()->json([], 204);
    }

    /**
     * @param Request $request
     * @param string $regionId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function availabilityZones(Request $request, string $regionId)
    {
        $availabilityZones = Region::findOrFail($regionId)->availabilityZones();

        return AvailabilityZoneResource::collection($availabilityZones->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateRegionRequest;
use App\Http\Requests\V2\UpdateRegionRequest;
use App\Models\V2\AvailabilityZone;
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Region::forUser($request->user);
        (new QueryTransformer($request))
            ->config(Region::class)
            ->transform($collection);

        return RegionResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $regionId
     * @return RegionResource
     */
    public function show(Request $request, string $regionId)
    {
        return new RegionResource(
            Region::forUser($request->user)->findOrFail($regionId)
        );
    }

    /**
     * @param CreateRegionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRegionRequest $request)
    {
        $region = new Region($request->only(['name', 'is_public']));
        $region->save();
        return $this->responseIdMeta($request, $region->getKey(), 201);
    }

    /**
     * @param UpdateRegionRequest $request
     * @param string $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRegionRequest $request, string $regionId)
    {
        $region = Region::findOrFail($regionId);
        $region->fill($request->only(['name', 'is_public']))->save();
        return $this->responseIdMeta($request, $region->getKey(), 200);
    }


    /**
     * @param Request $request
     * @param string $regionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $regionId)
    {
        $region = Region::findOrFail($regionId);
        try {
            $region->delete();
        } catch (\Exception $e) {
            return $region->getDeletionError($e);
        }
        return response()->json([], 204);
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $regionId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function availabilityZones(Request $request, QueryTransformer $queryTransformer, string $regionId)
    {
        $collection = Region::forUser(app('request')->user)->findOrFail($regionId)->availabilityZones();
        $queryTransformer->config(AvailabilityZone::class)
            ->transform($collection);

        return AvailabilityZoneResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $regionId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function vpcs(Request $request, QueryTransformer $queryTransformer, string $regionId)
    {
        $collection = Region::forUser(app('request')->user)->findOrFail($regionId)->vpcs();
        $queryTransformer->config(Vpc::class)
            ->transform($collection);

        return VpcResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateRegionRequest;
use App\Http\Requests\V2\UpdateRegionRequest;
use App\Models\V2\Product;
use App\Models\V2\Region;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\ProductResource;
use App\Resources\V2\RegionResource;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;

class RegionController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Region::forUser($request->user());

        return RegionResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $regionId)
    {
        return new RegionResource(
            Region::forUser($request->user())->findOrFail($regionId)
        );
    }

    public function create(CreateRegionRequest $request)
    {
        $region = new Region($request->only(['name', 'is_public']));
        $region->save();
        return $this->responseIdMeta($request, $region->id, 201);
    }

    public function update(UpdateRegionRequest $request, string $regionId)
    {
        $region = Region::findOrFail($regionId);
        $region->fill($request->only(['name', 'is_public']))->save();
        return $this->responseIdMeta($request, $region->id, 200);
    }

    public function destroy(Request $request, string $regionId)
    {
        Region::findOrFail($regionId)->delete();
        return response('', 204);
    }

    public function availabilityZones(Request $request, string $regionId)
    {
        $collection = Region::forUser($request->user())->findOrFail($regionId)->availabilityZones();

        return AvailabilityZoneResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function vpcs(Request $request, string $regionId)
    {
        $collection = Region::forUser($request->user())->findOrFail($regionId)->vpcs();

        return VpcResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function prices(Request $request, string $regionId)
    {
        $region = Region::forUser($request->user())->findOrFail($regionId);
        $products = Product::forRegion($region);

        return ProductResource::collection($products->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

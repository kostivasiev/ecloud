<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateRegionRequest;
use App\Http\Requests\V2\UpdateRegionRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\ProductResource;
use App\Resources\V2\RegionResource;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class RegionController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Region::forUser($request->user());
        (new QueryTransformer($request))
            ->config(Region::class)
            ->transform($collection);

        return RegionResource::collection($collection->paginate(
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

    public function availabilityZones(Request $request, QueryTransformer $queryTransformer, string $regionId)
    {
        $collection = Region::forUser($request->user())->findOrFail($regionId)->availabilityZones();
        $queryTransformer->config(AvailabilityZone::class)
            ->transform($collection);

        return AvailabilityZoneResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function vpcs(Request $request, QueryTransformer $queryTransformer, string $regionId)
    {
        $collection = Region::forUser($request->user())->findOrFail($regionId)->vpcs();
        $queryTransformer->config(Vpc::class)
            ->transform($collection);

        return VpcResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function prices(Request $request, string $regionId)
    {
        $region = Region::forUser($request->user())->findOrFail($regionId);
        $products = Product::forRegion($region);

        // Hacky Resource specific filtering
        (new QueryTransformer(Product::transformRequest($request)))
            ->config(Product::class)
            ->transform($products);

        return ProductResource::collection($products->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

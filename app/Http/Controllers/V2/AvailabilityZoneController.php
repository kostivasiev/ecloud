<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateAvailabilityZoneRequest;
use App\Http\Requests\V2\UpdateAvailabilityZoneRequest;
use App\Models\V2\AvailabilityZone;
use App\Resources\V2\AvailabilityZoneCapacityResource;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\DhcpResource;
use App\Resources\V2\HostSpecResource;
use App\Resources\V2\ImageResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerResource;
use App\Resources\V2\ProductResource;
use App\Resources\V2\ResourceTierResource;
use App\Resources\V2\RouterResource;
use App\Resources\V2\RouterThroughputResource;
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = AvailabilityZone::forUser($request->user());
        return AvailabilityZoneResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return AvailabilityZoneResource
     */
    public function show(Request $request, string $zoneId)
    {
        return new AvailabilityZoneResource(
            AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
        );
    }

    /**
     * @param CreateAvailabilityZoneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateAvailabilityZoneRequest $request)
    {
        $availabilityZone = new AvailabilityZone($request->only([
            'code',
            'name',
            'datacentre_site_id',
            'is_public',
            'region_id',
            'san_name',
            'ucs_compute_name',
            'resource_tier_id',
        ]));
        $availabilityZone->save();
        $availabilityZone->refresh();
        return $this->responseIdMeta($request, $availabilityZone->id, 201);
    }

    /**
     * @param UpdateAvailabilityZoneRequest $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAvailabilityZoneRequest $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        $availabilityZone->fill($request->only([
            'code',
            'name',
            'datacentre_site_id',
            'is_public',
            'region_id',
            'san_name',
            'ucs_compute_name',
            'resource_tier_id',
        ]));
        $availabilityZone->save();
        return $this->responseIdMeta($request, $availabilityZone->id, 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function routers(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->routers();

        return RouterResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function routerThroughputs(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->routerThroughputs();

        return RouterThroughputResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function dhcps(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->dhcps();

        return DhcpResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function credentials(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->credentials();

        return CredentialResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function instances(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->instances();

        return InstanceResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function loadBalancers(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->loadBalancers();

        return LoadBalancerResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function capacities(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->availabilityZoneCapacities();

        return AvailabilityZoneCapacityResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function destroy(string $zoneId)
    {
        AvailabilityZone::findOrFail($zoneId)->delete();
        return response('', 204);
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function prices(Request $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::forUser($request->user())->findOrFail($zoneId);

        $products = $availabilityZone->products();

        return ProductResource::collection($products->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function hostSpecs(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->hostSpecs();

        return HostSpecResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function images(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->images();

        return ImageResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function resourceTiers(Request $request, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->resourceTiers();

        return ResourceTierResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }
}

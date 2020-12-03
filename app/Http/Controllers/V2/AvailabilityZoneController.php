<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateAvailabilityZoneRequest;
use App\Http\Requests\V2\UpdateAvailabilityZoneRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Product;
use App\Models\V2\Router;
use App\Resources\V2\AvailabilityZonePricesResource;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\DhcpResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerClusterResource;
use App\Resources\V2\RouterResource;
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
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = AvailabilityZone::forUser($request->user);
        $queryTransformer->config(AvailabilityZone::class)
            ->transform($collection);

        return AvailabilityZoneResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return AvailabilityZoneResource
     */
    public function show(Request $request, string $zoneId)
    {
        return new AvailabilityZoneResource(
            AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
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
            'nsx_manager_endpoint',
            'nsx_edge_cluster_id',
        ]));
        $availabilityZone->save();
        $availabilityZone->refresh();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 201);
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
            'nsx_manager_endpoint',
            'nsx_edge_cluster_id',
        ]));
        $availabilityZone->save();
        return $this->responseIdMeta($request, $availabilityZone->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function routers(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
            ->routers();
        $queryTransformer->config(Router::class)
            ->transform($collection);

        return RouterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function dhcps(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
            ->dhcps();
        $queryTransformer->config(Dhcp::class)
            ->transform($collection);

        return DhcpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function credentials(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
            ->credentials();
        $queryTransformer->config(Credential::class)
            ->transform($collection);

        return CredentialResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function instances(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
            ->instances();
        $queryTransformer->config(Instance::class)
            ->transform($collection);

        return InstanceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function lbcs(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user)->findOrFail($zoneId)
            ->loadBalancerClusters();
        $queryTransformer->config(LoadBalancerCluster::class)
            ->transform($collection);

        return LoadBalancerClusterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($zoneId);
        try {
            $availabilityZone->delete();
        } catch (\Exception $e) {
            return $availabilityZone->getDeletionError($e);
        }
        return response()->json([], 204);
    }

    /**
     * @param Request $request
     * @param string $zoneId
     * @return AvailabilityZonePricesResource
     */
    public function prices(Request $request, string $zoneId)
    {
        $availabilityZone = AvailabilityZone::forUser($request->user)->findOrFail($zoneId);

        $products = $availabilityZone->products()->get(); // Hack - this is not an Eloquent relation.

        $resource = new \StdClass();
        $products->each(function ($product) use (&$resource) {
            $resource->availability_zone_id = $product->availabilityZoneId;
            $resource->{strtolower($product->product_subcategory)}[] = [
                $product->name => $product->price
            ];
        });


        return response()->json([
            'data' => $resource,
            'meta' => (object)[],
        ]);

//        return new AvailabilityZonePricesResource(
//            $resource
//        );
    }
}

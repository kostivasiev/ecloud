<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateAvailabilityZoneRequest;
use App\Http\Requests\V2\UpdateAvailabilityZoneRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\HostSpec;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Product;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Resources\V2\AvailabilityZoneCapacityResource;
use App\Resources\V2\AvailabilityZoneResource;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\DhcpResource;
use App\Resources\V2\HostSpecResource;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerClusterResource;
use App\Resources\V2\ProductResource;
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
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = AvailabilityZone::forUser($request->user());
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
            'nsx_manager_endpoint',
            'nsx_edge_cluster_id',
            'san_name',
            'ucs_compute_name',
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
            'nsx_manager_endpoint',
            'nsx_edge_cluster_id',
            'san_name',
            'ucs_compute_name',
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
    public function routers(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->routers();
        $queryTransformer->config(Router::class)
            ->transform($collection);

        return RouterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function routerThroughputs(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->routerThroughputs();
        $queryTransformer->config(RouterThroughput::class)
            ->transform($collection);

        return RouterThroughputResource::collection($collection->paginate(
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
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
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
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
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
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
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
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->loadBalancerClusters();
        $queryTransformer->config(LoadBalancerCluster::class)
            ->transform($collection);

        return LoadBalancerClusterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function capacities(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->availabilityZoneCapacities();
        $queryTransformer->config(AvailabilityZoneCapacity::class)
            ->transform($collection);

        return AvailabilityZoneCapacityResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function destroy(Request $request, string $zoneId)
    {
        $model = AvailabilityZone::findOrFail($zoneId);
        if (!$model->canDelete()) {
            return $model->getDeletionError();
        }
        $model->delete();
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

        // Hacky Resource specific filtering
        (new QueryTransformer(Product::transformRequest($request)))
            ->config(Product::class)
            ->transform($products);

        return ProductResource::collection($products->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function hostSpecs(Request $request, QueryTransformer $queryTransformer, string $zoneId)
    {
        $collection = AvailabilityZone::forUser($request->user())->findOrFail($zoneId)
            ->hostSpecs();
        $queryTransformer->config(HostSpec::class)
            ->transform($collection);

        return HostSpecResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

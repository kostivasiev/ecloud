<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateLoadBalancerClusterRequest;
use App\Http\Requests\V2\UpdateLoadBalancerClusterRequest;
use App\Models\V2\LoadBalancer;
use App\Resources\V2\LoadBalancerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class LoadBalancerController
 * @package App\Http\Controllers\V2
 */
class LoadBalancerController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = LoadBalancer::forUser($request->user());
        (new QueryTransformer($request))
            ->config(LoadBalancer::class)
            ->transform($collection);

        return LoadBalancerResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $loadBalancerId
     * @return LoadBalancerResource
     */
    public function show(Request $request, string $loadBalancerId)
    {
        return new LoadBalancerResource(
            LoadBalancer::forUser($request->user())->findOrFail($loadBalancerId)
        );
    }

    /**
     * @param CreateLoadBalancerClusterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateLoadBalancerClusterRequest $request)
    {
        $loadBalancer = new LoadBalancer(
            $request->only(['name', 'availability_zone_id', 'vpc_id', 'load_balancer_spec_id'])
        );
        $loadBalancer->save();
        return $this->responseIdMeta($request, $loadBalancer->id, 201);
    }

    /**
     * @param UpdateLoadBalancerClusterRequest $request
     * @param string $loadBalancerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateLoadBalancerClusterRequest $request, string $loadBalancerId)
    {
        $loadBalancer = LoadBalancer::forUser(Auth::user())->findOrFail($loadBalancerId);
        $loadBalancer->fill($request->only(['name', 'availability_zone_id', 'vpc_id', 'load_balancer_spec_id']));
        $loadBalancer->save();
        return $this->responseIdMeta($request, $loadBalancer->id, 200);
    }

    public function destroy(Request $request, string $loadBalancerId)
    {
        LoadBalancer::forUser($request->user())->findOrFail($loadBalancerId)->delete();
        return response('', 204);
    }
}

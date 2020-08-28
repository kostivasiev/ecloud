<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateLoadBalancerClusterRequest;
use App\Http\Requests\V2\UpdateLoadBalancerClusterRequest;
use App\Models\V2\LoadBalancerCluster;
use App\Resources\V2\LoadBalancerClusterResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class LoadBalancerClusterController
 * @package App\Http\Controllers\V2
 */
class LoadBalancerClusterController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = LoadBalancerCluster::forUser($request->user);
        (new QueryTransformer($request))
            ->config(LoadBalancerCluster::class)
            ->transform($collection);

        return LoadBalancerClusterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $lbcId
     * @return LoadBalancerClusterResource
     */
    public function show(Request $request, string $lbcId)
    {
        return new LoadBalancerClusterResource(
            LoadBalancerCluster::forUser($request->user)->findOrFail($lbcId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateLoadBalancerClusterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateLoadBalancerClusterRequest $request)
    {
        $loadBalancerCluster = new LoadBalancerCluster(
            $request->only(['name', 'availability_zone_id', 'vpc_id', 'nodes'])
        );
        $loadBalancerCluster->save();
        return $this->responseIdMeta($request, $loadBalancerCluster->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateLoadBalancerClusterRequest $request
     * @param string $lbcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateLoadBalancerClusterRequest $request, string $lbcId)
    {
        $loadBalancerCluster = LoadBalancerCluster::forUser(app('request')->user)->findOrFail($lbcId);
        $loadBalancerCluster->fill($request->only(['name', 'availability_zone_id', 'vpc_id', 'nodes']));
        $loadBalancerCluster->save();
        return $this->responseIdMeta($request, $loadBalancerCluster->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $lbcId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $lbcId)
    {
        LoadBalancerCluster::forUser($request->user)->findOrFail($lbcId)->delete();
        return response()->json([], 204);
    }
}

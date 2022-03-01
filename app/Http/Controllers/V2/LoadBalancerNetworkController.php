<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\LoadBalancerNetwork\Create;
use App\Http\Requests\V2\LoadBalancerNetwork\Update;
use App\Models\V2\LoadBalancerNetwork;
use App\Resources\V2\LoadBalancerNetworkResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class LoadBalancerNetworkController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = LoadBalancerNetwork::forUser($request->user());

        $queryTransformer->config(LoadBalancerNetwork::class)
            ->transform($collection);

        return LoadBalancerNetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $loadBalancerNetworkId)
    {
        return new LoadBalancerNetworkResource(
            LoadBalancerNetwork::forUser($request->user())->findOrFail($loadBalancerNetworkId)
        );
    }

    public function store(Create $request)
    {
        $resource = new LoadBalancerNetwork($request->only([
            'name',
            'load_balancer_id',
            'network_id',
        ]));
        $task = $resource->syncSave();
        return $this->responseIdMeta($request, $resource->id, 202, $task->id);
    }

    public function update(Update $request, string $loadBalancerNetworkId)
    {
        $resource = LoadBalancerNetwork::forUser($request->user())->findOrFail($loadBalancerNetworkId);
        $resource->fill($request->only([
            'name',
        ]));
        $task = $resource->syncSave();
        return $this->responseIdMeta($request, $resource->id, 202, $task->id);
    }

    public function destroy(Request $request, string $loadBalancerNetworkId)
    {
        $resource = LoadBalancerNetwork::forUser($request->user())->findOrFail($loadBalancerNetworkId);

        $task = $resource->syncDelete();
        return $this->responseTaskId($task->id);
    }
}

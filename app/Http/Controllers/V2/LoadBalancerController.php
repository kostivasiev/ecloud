<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\LoadBalancer\CreateRequest;
use App\Http\Requests\V2\LoadBalancer\UpdateRequest;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerNetworkResource;
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

        return LoadBalancerResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
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
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $loadBalancer = new LoadBalancer(
            $request->only(['name', 'availability_zone_id', 'vpc_id', 'load_balancer_spec_id'])
        );

        $task = $loadBalancer->syncSave(['network_ids' => [$request->input('network_id')]]);
        return $this->responseIdMeta($request, $loadBalancer->id, 202, $task->id);
    }

    /**
     * @param UpdateRequest $request
     * @param string $loadBalancerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $loadBalancerId)
    {
        $loadBalancer = LoadBalancer::forUser(Auth::user())->findOrFail($loadBalancerId);
        $loadBalancer->fill($request->only(['name']));
        $task = $loadBalancer->syncSave();
        return $this->responseIdMeta($request, $loadBalancer->id, 202, $task->id);
    }

    public function destroy(Request $request, string $loadBalancerId)
    {
        $loadBalancer = LoadBalancer::forUser(Auth::user())->findOrFail($loadBalancerId);
        $task = $loadBalancer->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function nodes(Request $request, string $loadBalancerId)
    {
        $collection = LoadBalancer::forUser($request->user())
            ->findOrFail($loadBalancerId)
            ->instances();

        return InstanceResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function networks(Request $request, string $loadBalancerId)
    {
        $collection = LoadBalancer::forUser($request->user())->findOrFail($loadBalancerId)->loadBalancerNetworks();

        return LoadBalancerNetworkResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function instances(Request $request, string $loadBalancerId)
    {
        $lb = LoadBalancer::forUser($request->user())
            ->with('networks.router.networks.nics.instance')
            ->findOrFail($loadBalancerId);

        
        foreach ($lb->networks as $lbNetwork) {
            foreach ($lbNetwork->router->networks as $siblingNetwork) {
                foreach ($siblingNetwork->nics as $nic) {
                    dump($nic->instance->name);
                }
            }
        }
    }
}

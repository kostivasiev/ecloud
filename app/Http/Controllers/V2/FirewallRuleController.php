<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateFirewallRuleRequest;
use App\Http\Requests\V2\UpdateFirewallRuleRequest;
use App\Models\V2\FirewallRule;
use App\Resources\V2\FirewallRuleResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class FirewallRuleController
 * @package App\Http\Controllers\V2
 */
class FirewallRuleController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallRule::forUser($request->user);

        $queryTransformer->config(FirewallRule::class)
            ->transform($collection);

        return FirewallRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallRuleId
     * @return FirewallRuleResource
     */
    public function show(Request $request, string $firewallRuleId)
    {
        return new FirewallRuleResource(
            FirewallRule::forUser($request->user)->findOrFail($firewallRuleId)
        );
    }

    /**
     * @param CreateFirewallRuleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateFirewallRuleRequest $request)
    {
        $instance = new FirewallRule();
        $instance->fill($request->only([
            'name', 'router_id', 'deployed', 'firewall_policy_id', 'service_type', 'source', 'source_ports',
            'destination', 'destination_ports', 'action', 'direction', 'enabled'
        ]));
        $instance->save();
        $instance->refresh();
        return $this->responseIdMeta($request, $instance->getKey(), 201);
    }

    /**
     * @param UpdateFirewallRuleRequest $request
     * @param string $firewallRuleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFirewallRuleRequest $request, string $firewallRuleId)
    {
        $item = FirewallRule::foruser(app('request')->user)->findOrFail($firewallRuleId);
        $item->fill($request->only([
            'name', 'router_id', 'deployed', 'firewall_policy_id', 'service_type', 'source', 'source_ports',
            'destination', 'destination_ports', 'action', 'direction', 'enabled'
        ]));
        $item->save();
        return $this->responseIdMeta($request, $item->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallRuleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $firewallRuleId)
    {
        $item = FirewallRule::foruser(app('request')->user)->findOrFail($firewallRuleId);
        $item->delete();
        return response()->json([], 204);
    }
}

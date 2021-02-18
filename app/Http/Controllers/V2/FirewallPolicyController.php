<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateFirewallPolicyRequest;
use App\Http\Requests\V2\UpdateFirewallPolicyRequest;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Resources\V2\FirewallPolicyResource;
use App\Resources\V2\FirewallRuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class FirewallPolicyController
 * @package App\Http\Controllers\V2
 */
class FirewallPolicyController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallPolicy::forUser($request->user());

        $queryTransformer->config(FirewallPolicy::class)
            ->transform($collection);

        return FirewallPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallPolicyId
     * @return FirewallPolicyResource
     */
    public function show(Request $request, string $firewallPolicyId)
    {
        return new FirewallPolicyResource(
            FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)
        );
    }

    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $routerId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function firewallRules(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = FirewallPolicy::forUser($request->user())->findOrFail($routerId)->firewallRules();
        $queryTransformer->config(FirewallRule::class)
            ->transform($collection);

        return FirewallRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param CreateFirewallPolicyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateFirewallPolicyRequest $request)
    {
        $policy = new FirewallPolicy();
        $policy->fill($request->only(['name', 'sequence', 'router_id']));
        $policy->save();
        $policy->refresh();
        return $this->responseIdMeta($request, $policy->getKey(), 201);
    }

    /**
     * @param UpdateFirewallPolicyRequest $request
     * @param string $firewallPolicyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFirewallPolicyRequest $request, string $firewallPolicyId)
    {
        $policy = FirewallPolicy::forUser(Auth::user())->findOrFail($firewallPolicyId);
        $policy->fill($request->only(['name', 'sequence', 'router_id']));
        $policy->save();
        return $this->responseIdMeta($request, $policy->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $firewallPolicyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $firewallPolicyId)
    {
        $policy = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId);
        $policy->delete();
        return response()->json([], 204);
    }
}

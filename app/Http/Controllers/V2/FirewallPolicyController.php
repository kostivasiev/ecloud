<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateFirewallPolicyRequest;
use App\Http\Requests\V2\UpdateFirewallPolicyRequest;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Task;
use App\Resources\V2\FirewallPolicyResource;
use App\Resources\V2\FirewallRuleResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class FirewallPolicyController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = FirewallPolicy::forUser($request->user());

        $queryTransformer->config(FirewallPolicy::class)
            ->transform($collection);

        return FirewallPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $firewallPolicyId)
    {
        return new FirewallPolicyResource(
            FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)
        );
    }

    public function firewallRules(Request $request, QueryTransformer $queryTransformer, string $firewallPolicyId)
    {
        $collection = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)->firewallRules();
        $queryTransformer->config(FirewallRule::class)
            ->transform($collection);

        return FirewallRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function store(CreateFirewallPolicyRequest $request)
    {
        $model = app()->make(FirewallPolicy::class);
        $model->fill($request->only(['name', 'sequence', 'router_id']));

        $model->withTaskLock(function ($policy) {
            $policy->save();
        });

        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function update(UpdateFirewallPolicyRequest $request, string $firewallPolicyId)
    {
        $model = FirewallPolicy::forUser(Auth::user())->findOrFail($firewallPolicyId);
        $model->fill($request->only(['name', 'sequence']));

        $model->withTaskLock(function ($policy) {
            $policy->save();
        });

        return $this->responseIdMeta($request, $model->id, 202);
    }

    public function destroy(Request $request, string $firewallPolicyId)
    {
        $model = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId);

        $model->withTaskLock(function ($model) {
            $model->delete();
        });

        return response('', 202);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $firewallPolicyId)
    {
        $collection = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateFirewallPolicyRequest;
use App\Http\Requests\V2\UpdateFirewallPolicyRequest;
use App\Models\V2\FirewallPolicy;
use App\Resources\V2\FirewallPolicyResource;
use App\Resources\V2\FirewallRuleResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirewallPolicyController extends BaseController
{
    public function index(Request $request)
    {
        $collection = FirewallPolicy::forUser($request->user());

        return FirewallPolicyResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(Request $request, string $firewallPolicyId)
    {
        return new FirewallPolicyResource(
            FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)
        );
    }

    public function firewallRules(Request $request, string $firewallPolicyId)
    {
        $collection = FirewallPolicy::forUser($request->user())
            ->findOrFail($firewallPolicyId)->firewallRules();

        return FirewallRuleResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function store(CreateFirewallPolicyRequest $request)
    {
        $model = app()->make(FirewallPolicy::class);
        $model->fill($request->only(['name', 'sequence', 'router_id']));
        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(UpdateFirewallPolicyRequest $request, string $firewallPolicyId)
    {
        $model = FirewallPolicy::forUser(Auth::user())->findOrFail($firewallPolicyId);
        $model->fill($request->only(['name', 'sequence']));

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $firewallPolicyId)
    {
        $model = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId);

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, string $firewallPolicyId)
    {
        $collection = FirewallPolicy::forUser($request->user())->findOrFail($firewallPolicyId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

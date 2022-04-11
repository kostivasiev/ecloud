<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkPolicy\Create;
use App\Http\Requests\V2\NetworkPolicy\Update;
use App\Models\V2\NetworkPolicy;
use App\Resources\V2\NetworkPolicyResource;
use App\Resources\V2\NetworkRuleResource;
use App\Resources\V2\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkPolicyController extends BaseController
{
    public function index(Request $request)
    {
        $collection = NetworkPolicy::forUser($request->user());

        return NetworkPolicyResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkPolicyId)
    {
        return new NetworkPolicyResource(NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId));
    }

    public function store(Create $request)
    {
        $model = app()->make(NetworkPolicy::class);
        $model->fill($request->only([
            'name',
            'network_id',
        ]));
        if ($request->user()->isAdmin()) {
            $model->locked = $request->input('locked', false);
        }

        $task = $model->syncSave(['catchall_rule_action' => $request->input('catchall_rule_action', 'REJECT')]);

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(Update $request, string $networkPolicyId)
    {
        $model = NetworkPolicy::forUser(Auth::user())->findOrFail($networkPolicyId);
        $model->fill($request->only(['name']));

        // TODO: we don't really need to trigger a sync here.
        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $networkPolicyId)
    {
        $model = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId);

        $task = $model->syncDelete();

        return $this->responseTaskId($task->id);
    }

    public function tasks(Request $request, string $networkPolicyId)
    {
        $collection = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
    
    public function networkRules(Request $request, string $networkPolicyId)
    {
        $collection = NetworkPolicy::forUser($request->user())
            ->findOrFail($networkPolicyId)
            ->networkRules();

        return NetworkRuleResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function lock(Request $request, $networkPolicyId)
    {
        $networkPolicy = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId);
        $networkPolicy->locked = true;
        $networkPolicy->save();

        return response('', 204);
    }

    public function unlock(Request $request, $networkPolicyId)
    {
        $networkPolicy = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId);
        $networkPolicy->locked = false;
        $networkPolicy->save();

        return response('', 204);
    }
}

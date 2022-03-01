<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkPolicy\Create;
use App\Http\Requests\V2\NetworkPolicy\Update;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\Task;
use App\Resources\V2\NetworkPolicyResource;
use App\Resources\V2\TaskResource;
use App\Resources\V2\NetworkRuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkPolicyController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkPolicy::forUser($request->user());
        $queryTransformer->config(NetworkPolicy::class)
            ->transform($collection);

        return NetworkPolicyResource::collection($collection->paginate(
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

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $networkPolicyId)
    {
        $collection = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
    
    public function networkRules(Request $request, QueryTransformer $queryTransformer, string $networkPolicyId)
    {
        $collection = NetworkPolicy::forUser($request->user())->findOrFail($networkPolicyId)->networkRules();
        $queryTransformer->config(NetworkRule::class)
            ->transform($collection);

        return NetworkRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

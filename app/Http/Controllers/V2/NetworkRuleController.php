<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\V2\TaskException;
use App\Http\Requests\V2\NetworkRule\Create;
use App\Http\Requests\V2\NetworkRule\Update;
use App\Models\V2\NetworkRule;
use App\Resources\V2\NetworkRuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkRuleController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkRule::forUser($request->user());

        $queryTransformer->config(NetworkRule::class)
            ->transform($collection);

        return NetworkRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkRuleId)
    {
        return new NetworkRuleResource(NetworkRule::forUser($request->user())->findOrFail($networkRuleId));
    }

    public function store(Create $request)
    {
        $networkRule = app()->make(NetworkRule::class);
        $networkRule->fill($request->only([
            'network_policy_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'direction',
            'enabled',
        ]));

        $networkRule->save();

        $task = $networkRule->networkPolicy->syncSave();

        return $this->responseIdMeta($request, $networkRule->id, 202, $task->id);
    }

    public function update(Update $request, string $networkRuleId)
    {
        $networkRule = NetworkRule::forUser(Auth::user())->findOrFail($networkRuleId);

        $fillable = [
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'direction',
            'enabled',
        ];

        if ($networkRule->type == NetworkRule::TYPE_CATCHALL && !Auth::user()->isAdmin()) {
            $fillable = 'action';
        }

        $networkRule->fill($request->only($fillable));

        $networkRule->save();

        $task = $networkRule->networkPolicy->syncSave();

        return $this->responseIdMeta($request, $networkRule->id, 202, $task->id);
    }

    public function destroy(Request $request, string $networkRuleId)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($networkRuleId);

        $networkRule->networkRulePorts->each(function ($port) {
            $port->delete();
        });

        $networkRule->delete();

        // We don't actually need to do this due to the delete listener deleting the rule,
        // but that logic needs to be moved into the resource sync jobs so lets keep this for now.
        $task = $networkRule->networkPolicy->syncSave();

        return $this->responseTaskId($task->id);
    }
}

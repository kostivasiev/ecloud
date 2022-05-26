<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkRule\Create;
use App\Http\Requests\V2\NetworkRule\Update;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Resources\V2\NetworkRuleResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkRuleController extends BaseController
{
    public function index(Request $request)
    {
        $collection = NetworkRule::forUser($request->user());

        return NetworkRuleResource::collection($collection->search()->paginate(
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

        $task = $networkRule->networkPolicy->withTaskLock(function () use ($request, $networkRule) {
            $networkRule->save();

            if ($request->has('ports')) {
                foreach ($request->input('ports') as $port) {
                    $port['network_rule_id'] = $networkRule->id;
                    $networkRulePort = new NetworkRulePort($port);
                    $networkRulePort->save();
                }
            }

            return $networkRule->networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

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

        $task = $networkRule->networkPolicy->withTaskLock(function () use ($request, $networkRule) {
            $networkRule->save();

            if ($request->filled('ports')) {
                $networkRule->networkRulePorts->each(function ($port) {
                    $port->delete();
                });
                foreach ($request->input('ports') as $port) {
                    $port['network_rule_id'] = $networkRule->id;
                    $networkRulePort = new NetworkRulePort($port);
                    $networkRulePort->save();
                }
            }

            return $networkRule->networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $networkRule->id, 202, $task->id);
    }

    public function destroy(Request $request, string $networkRuleId)
    {
        $networkRule = NetworkRule::forUser($request->user())->findOrFail($networkRuleId);

        $task = $networkRule->networkPolicy->withTaskLock(function () use ($networkRule) {
            $networkRule->networkRulePorts->each(function ($port) {
                $port->delete();
            });

            $networkRule->delete();

            return $networkRule->networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }
}

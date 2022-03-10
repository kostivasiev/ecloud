<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkRulePort\Create;
use App\Http\Requests\V2\NetworkRulePort\Update;
use App\Models\V2\NetworkRulePort;
use App\Resources\V2\NetworkRulePortResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkRulePortController extends BaseController
{
    public function index(Request $request)
    {
        $collection = NetworkRulePort::forUser($request->user());

        return NetworkRulePortResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $networkRulePortId)
    {
        return new NetworkRulePortResource(NetworkRulePort::forUser($request->user())->findOrFail($networkRulePortId));
    }

    public function store(Create $request)
    {
        $networkRulePort = app()->make(NetworkRulePort::class);
        $networkRulePort->fill($request->only([
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));

        $task = $networkRulePort->networkRule->networkPolicy->withTaskLock(function ($networkPolicy) use ($networkRulePort) {
            $networkRulePort->save();
            return $networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $networkRulePort->id, 202, $task->id);
    }

    public function update(Update $request, string $networkRulePortId)
    {
        $networkRulePort = NetworkRulePort::forUser(Auth::user())->findOrFail($networkRulePortId);
        $networkRulePort->fill($request->only([
            'name',
            'protocol',
            'source',
            'destination',
        ]));

        $task = $networkRulePort->networkRule->networkPolicy->withTaskLock(function ($networkPolicy) use ($networkRulePort) {
            $networkRulePort->save();
            return $networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $networkRulePort->id, 202, $task->id);
    }

    public function destroy(Request $request, string $networkRulePortId)
    {
        $networkRulePort = NetworkRulePort::forUser($request->user())->findOrFail($networkRulePortId);

        $task = $networkRulePort->networkRule->networkPolicy->withTaskLock(function ($networkPolicy) use ($networkRulePort) {
            $networkRulePort->delete();
            return $networkPolicy->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseTaskId($task->id);
    }
}

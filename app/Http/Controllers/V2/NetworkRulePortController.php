<?php

namespace App\Http\Controllers\V2;

use App\Exceptions\V2\TaskException;
use App\Http\Requests\V2\NetworkRulePort\Create;
use App\Http\Requests\V2\NetworkRulePort\Update;
use App\Models\V2\NetworkRulePort;
use App\Resources\V2\NetworkRulePortResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkRulePortController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkRulePort::forUser($request->user());
        $queryTransformer->config(NetworkRulePort::class)
            ->transform($collection);

        return NetworkRulePortResource::collection($collection->paginate(
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

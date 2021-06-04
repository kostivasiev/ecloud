<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkRulePort\Create;
use App\Http\Requests\V2\NetworkRulePort\Update;
use App\Models\V2\NetworkRulePort;
use App\Resources\V2\NetworkRulePortResource;
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
        $resource = app()->make(NetworkRulePort::class);
        $resource->fill($request->only([
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $resource->save();

        $task = $resource->networkRule->networkPolicy->syncSave();

        return $this->responseIdMeta($request, $resource->id, 202, $task->id);
    }

    public function update(Update $request, string $networkRulePortId)
    {
        $resource = NetworkRulePort::forUser(Auth::user())->findOrFail($networkRulePortId);
        $resource->fill($request->only([
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $resource->save();

        $task = $resource->networkRule->networkPolicy->syncSave();

        return $this->responseTaskId($task->id);
    }

    public function destroy(Request $request, string $networkRulePortId)
    {
        $resource = NetworkRulePort::forUser($request->user())->findOrFail($networkRulePortId);
        $resource->delete();

        $task = $resource->networkRule->networkPolicy->syncSave();

        return $this->responseTaskId($task->id);
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkRulePort\Create;
use App\Http\Requests\V2\NetworkRulePort\Update;
use App\Models\V2\NetworkRulePort;
use App\Resources\V2\NetworkRulePortResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkRulePortController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkRulePort::forUser($request->user);
        (new QueryTransformer($request))
            ->config(NetworkRulePort::class)
            ->transform($collection);
        return NetworkRulePortResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $id)
    {
        return new NetworkRulePortResource(NetworkRulePort::forUser($request->user)->findOrFail($id));
    }

    public function store(Create $request)
    {
        $model = app()->make(NetworkRulePort::class);
        $model->fill($request->only([
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->getKey(), 201);
    }

    public function update(Update $request, string $id)
    {
        $model = NetworkRulePort::forUser(app('request')->user)->findOrFail($id);
        $model->fill($request->only([
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $model->save();
        return $this->responseIdMeta($request, $model->getKey(), 200);
    }

    public function destroy(Request $request, string $id)
    {
        NetworkRulePort::forUser(app('request')->user)->findOrFail($id)
            ->delete();
        return response('', 204);
    }
}

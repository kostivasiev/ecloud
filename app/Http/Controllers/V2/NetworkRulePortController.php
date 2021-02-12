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
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
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

    /**
     * @param Request $request
     * @param string $networkRuleId
     * @return NetworkRulePortResource
     */
    public function show(Request $request, string $networkRuleId)
    {
        return new NetworkRulePortResource(
            NetworkRulePort::forUser($request->user)->findOrFail($networkRuleId)
        );
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Create $request)
    {
        $aclRulePort = app()->make(NetworkRulePort::class);
        $aclRulePort->fill($request->only([
            'network_acl_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $aclRulePort->save();
        return $this->responseIdMeta($request, $aclRulePort->getKey(), 201);
    }

    /**
     * @param Update $request
     * @param string $networkRuleId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(Update $request, string $networkRuleId)
    {
        $aclRulePort = NetworkRulePort::forUser(app('request')->user)->findOrFail($networkRuleId);
        $aclRulePort->fill($request->only([
            'network_acl_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ]));
        $aclRulePort->save();
        return $this->responseIdMeta($request, $aclRulePort->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkRuleId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkRuleId)
    {
        $aclRulePort = NetworkRulePort::forUser(app('request')->user)->findOrFail($networkRuleId);
        $aclRulePort->delete();
        return response('', 204);
    }
}

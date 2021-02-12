<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkAclRulePort\CreateRequest;
use App\Http\Requests\V2\NetworkAclRulePort\UpdateRequest;
use App\Models\V2\NetworkAclRulePort;
use App\Resources\V2\NetworkAclRulePortResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkAclRulePortController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkAclRulePort::forUser($request->user);

        (new QueryTransformer($request))
            ->config(NetworkAclRulePort::class)
            ->transform($collection);

        return NetworkAclRulePortResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkAclRuleId
     * @return NetworkAclRulePortResource
     */
    public function show(Request $request, string $networkAclRuleId)
    {
        return new NetworkAclRulePortResource(
            NetworkAclRulePort::forUser($request->user)->findOrFail($networkAclRuleId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(CreateRequest $request)
    {
        $aclRulePort = app()->make(NetworkAclRulePort::class);
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
     * @param UpdateRequest $request
     * @param string $networkAclRuleId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(UpdateRequest $request, string $networkAclRuleId)
    {
        $aclRulePort = NetworkAclRulePort::forUser(app('request')->user)->findOrFail($networkAclRuleId);
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
     * @param string $networkAclRuleId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkAclRuleId)
    {
        $aclRulePort = NetworkAclRulePort::forUser(app('request')->user)->findOrFail($networkAclRuleId);
        $aclRulePort->delete();
        return response('', 204);
    }
}

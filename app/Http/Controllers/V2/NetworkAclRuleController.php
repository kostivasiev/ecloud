<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkAclRule\CreateRequest;
use App\Http\Requests\V2\NetworkAclRule\UpdateRequest;
use App\Models\V2\NetworkAclRule;
use App\Resources\V2\NetworkAclRuleResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkAclRuleController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkAclRule::forUser($request->user);

        (new QueryTransformer($request))
            ->config(NetworkAclRule::class)
            ->transform($collection);

        return NetworkAclRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkAclRuleId
     * @return NetworkAclRuleResource
     */
    public function show(Request $request, string $networkAclRuleId)
    {
        return new NetworkAclRuleResource(
            NetworkAclRule::forUser($request->user)->findOrFail($networkAclRuleId)
        );
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(CreateRequest $request)
    {
        $aclRule = app()->make(NetworkAclRule::class);
        $aclRule->fill($request->only([
            'network_acl_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'enabled',
        ]));
        $aclRule->save();
        return $this->responseIdMeta($request, $aclRule->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $networkAclRuleId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(UpdateRequest $request, string $networkAclRuleId)
    {
        $aclRule = NetworkAclRule::forUser(app('request')->user)->findOrFail($networkAclRuleId);
        $aclRule->fill($request->only([
            'network_acl_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'enabled',
        ]));
        $aclRule->save();
        return $this->responseIdMeta($request, $aclRule->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkAclRuleId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkAclRuleId)
    {
        $aclRule = NetworkAclRule::forUser(app('request')->user)->findOrFail($networkAclRuleId);
        $aclRule->delete();
        return response('', 204);
    }
}

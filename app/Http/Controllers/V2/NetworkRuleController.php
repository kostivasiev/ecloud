<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkRule\Create;
use App\Http\Requests\V2\NetworkRule\Update;
use App\Models\V2\NetworkRule;
use App\Resources\V2\NetworkRuleResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkRuleController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkRule::forUser($request->user);

        (new QueryTransformer($request))
            ->config(NetworkRule::class)
            ->transform($collection);

        return NetworkRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkRuleId
     * @return NetworkRuleResource
     */
    public function show(Request $request, string $networkRuleId)
    {
        return new NetworkRuleResource(
            NetworkRule::forUser($request->user)->findOrFail($networkRuleId)
        );
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Create $request)
    {
        $aclRule = app()->make(NetworkRule::class);
        $aclRule->fill($request->only([
            'network_policy_id',
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
     * @param Update $request
     * @param string $networkRuleId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(Update $request, string $networkRuleId)
    {
        $aclRule = NetworkRule::forUser(app('request')->user)->findOrFail($networkRuleId);
        $aclRule->fill($request->only([
            'network_policy_id',
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
     * @param string $networkRuleId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkRuleId)
    {
        $aclRule = NetworkRule::forUser(app('request')->user)->findOrFail($networkRuleId);
        $aclRule->delete();
        return response('', 204);
    }
}

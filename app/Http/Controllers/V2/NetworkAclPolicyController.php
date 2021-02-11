<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkAclPolicy\CreateRequest;
use App\Http\Requests\V2\NetworkAclPolicy\UpdateRequest;
use App\Models\V2\NetworkAclPolicy;
use App\Resources\V2\NetworkAclPolicyResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkAclPolicyController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkAclPolicy::forUser($request->user);
        $queryTransformer->config(NetworkAclPolicy::class)
            ->transform($collection);

        return NetworkAclPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $aclPolicyId
     * @return NetworkAclPolicyResource
     */
    public function show(Request $request, string $aclPolicyId)
    {
        return new NetworkAclPolicyResource(NetworkAclPolicy::forUser($request->user)->findOrFail($aclPolicyId));
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $aclPolicy = new NetworkAclPolicy($request->only([
            'name',
            'network_id',
            'vpc_id',
        ]));
        $aclPolicy->save();
        return $this->responseIdMeta($request, $aclPolicy->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $aclPolicyId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(UpdateRequest $request, string $aclPolicyId)
    {
        $aclPolicy = NetworkAclPolicy::forUser(app('request')->user)->findOrFail($aclPolicyId);
        $aclPolicy->update($request->all());
        $aclPolicy->save();
        return $this->responseIdMeta($request, $aclPolicy->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $aclPolicyId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $aclPolicyId)
    {
        $aclPolicy = NetworkAclPolicy::forUser(app('request')->user)->findOrFail($aclPolicyId);
        $aclPolicy->delete();
        return response('', 204);
    }
}

<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\AclPolicy\CreateRequest;
use App\Http\Requests\V2\AclPolicy\UpdateRequest;
use App\Models\V2\AclPolicy;
use App\Resources\V2\AclPolicyResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class AclPolicyController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = AclPolicy::forUser($request->user);
        $queryTransformer->config(AclPolicy::class)
            ->transform($collection);

        return AclPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $aclPolicyId
     * @return AclPolicyResource
     */
    public function show(Request $request, string $aclPolicyId)
    {
        return new AclPolicyResource(AclPolicy::forUser($request->user)->findOrFail($aclPolicyId));
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $aclPolicy = new AclPolicy($request->only([
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
        $aclPolicy = AclPolicy::forUser(app('request')->user)->findOrFail($aclPolicyId);
        $aclPolicy->update($request->all());
        if (!$aclPolicy->save()) {
            return $aclPolicy->getSyncError();
        }
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
        $aclPolicy = AclPolicy::forUser(app('request')->user)->findOrFail($aclPolicyId);
        if (!$aclPolicy->delete()) {
            return $aclPolicy->getSyncError();
        }
        return response('', 204);
    }
}
<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkPolicy\Create;
use App\Http\Requests\V2\NetworkPolicy\Update;
use App\Models\V2\NetworkPolicy;
use App\Resources\V2\NetworkPolicyResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkPolicyController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkPolicy::forUser($request->user);
        $queryTransformer->config(NetworkPolicy::class)
            ->transform($collection);

        return NetworkPolicyResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkAclId
     * @return NetworkPolicyResource
     */
    public function show(Request $request, string $networkAclId)
    {
        return new NetworkPolicyResource(NetworkPolicy::forUser($request->user)->findOrFail($networkAclId));
    }

    /**
     * @param Create $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Create $request)
    {
        $networkPolicy = app()->make(NetworkPolicy::class);
        $networkPolicy->fill($request->only([
            'name',
            'network_id',
            'vpc_id'
        ]));
        $networkPolicy->save();
        return $this->responseIdMeta($request, $networkPolicy->getKey(), 201);
    }

    /**
     * @param Update $request
     * @param string $networkAclId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(Update $request, string $networkAclId)
    {
        $networkPolicy = NetworkPolicy::forUser(app('request')->user)->findOrFail($networkAclId);
        $networkPolicy->fill($request->only([
            'name',
            'network_id',
            'vpc_id',
        ]));
        $networkPolicy->save();
        return $this->responseIdMeta($request, $networkPolicy->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkAclId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkAclId)
    {
        $networkPolicy = NetworkPolicy::forUser(app('request')->user)->findOrFail($networkAclId);
        $networkPolicy->delete();
        return response('', 204);
    }
}

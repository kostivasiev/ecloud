<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\NetworkAcl\CreateRequest;
use App\Http\Requests\V2\NetworkAcl\UpdateRequest;
use App\Models\V2\Network;
use App\Models\V2\NetworkAcl;
use App\Models\V2\Vpc;
use App\Resources\V2\NetworkAclResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class NetworkAclController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = NetworkAcl::forUser($request->user);
        $queryTransformer->config(NetworkAcl::class)
            ->transform($collection);

        return NetworkAclResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkAclId
     * @return NetworkAclResource
     */
    public function show(Request $request, string $networkAclId)
    {
        return new NetworkAclResource(NetworkAcl::forUser($request->user)->findOrFail($networkAclId));
    }

    /**
     * @param CreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateRequest $request)
    {
        $aclPolicy = app()->make(NetworkAcl::class);
        $aclPolicy->fill($request->only([
            'name',
            'network_id',
            'vpc_id'
        ]));
//        $aclPolicy->network()
//            ->associate(
//                Network::forUser(app('request')->user)
//                    ->findOrFail($request->get('network_id'))
//            );
//        $aclPolicy->vpc()
//            ->associate(
//                Vpc::forUser(app('request')->user)
//                    ->findOrFail($request->get('vpc_id'))
//            );
        $aclPolicy->save();
        return $this->responseIdMeta($request, $aclPolicy->getKey(), 201);
    }

    /**
     * @param UpdateRequest $request
     * @param string $networkAclId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function update(UpdateRequest $request, string $networkAclId)
    {
        $aclPolicy = NetworkAcl::forUser(app('request')->user)->findOrFail($networkAclId);
        $aclPolicy->fill($request->only([
            'name',
            'network_id',
            'vpc_id',
        ]));
        $aclPolicy->save();
        return $this->responseIdMeta($request, $aclPolicy->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkAclId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkAclId)
    {
        $aclPolicy = NetworkAcl::forUser(app('request')->user)->findOrFail($networkAclId);
        $aclPolicy->delete();
        return response('', 204);
    }
}

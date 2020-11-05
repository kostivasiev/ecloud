<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateRouterRequest;
use App\Http\Requests\V2\UpdateRouterRequest;
use App\Models\V2\FirewallRule;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use App\Resources\V2\FirewallRuleResource;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\RouterResource;
use App\Resources\V2\VpnResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class RouterController
 * @package App\Http\Controllers\V2
 */
class RouterController extends BaseController
{
    /**
     * Get routers collection
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Router::forUser($request->user);

        (new QueryTransformer($request))
            ->config(Router::class)
            ->transform($collection);

        return RouterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $routerId
     * @return RouterResource
     */
    public function show(Request $request, string $routerId)
    {
        return new RouterResource(
            Router::forUser($request->user)->findOrFail($routerId)
        );
    }

    /**
     * @param CreateRouterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRouterRequest $request)
    {
        $router = new Router($request->only(['name', 'vpc_id', 'availability_zone_id']));
        $router->save();
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    /**
     * @param UpdateRouterRequest $request
     * @param string $routerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRouterRequest $request, string $routerId)
    {
        $router = Router::forUser(app('request')->user)->findOrFail($routerId);
        $router->fill($request->only(['name', 'vpc_id', 'availability_zone_id']));
        $router->save();
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $routerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $routerId)
    {
        $router = Router::forUser($request->user)->findOrFail($routerId);
        try {
            $router->delete();
        } catch (\Exception $e) {
            return $router->getDeletionError($e);
        }
        return response()->json([], 204);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $routerId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function vpns(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user)->findOrFail($routerId)->vpns();
        $queryTransformer->config(Vpn::class)
            ->transform($collection);

        return VpnResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $routerId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function firewallRules(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user)->findOrFail($routerId)->firewallRules();
        $queryTransformer->config(FirewallRule::class)
            ->transform($collection);

        return FirewallRuleResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param QueryTransformer $queryTransformer
     * @param string $routerId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function networks(Request $request, QueryTransformer $queryTransformer, string $routerId)
    {
        $collection = Router::forUser($request->user)->findOrFail($routerId)->networks();
        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

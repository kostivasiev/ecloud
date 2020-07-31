<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Router\AfterCreateEvent;
use App\Events\V2\Router\AfterDeleteEvent;
use App\Events\V2\Router\AfterUpdateEvent;
use App\Events\V2\Router\BeforeCreateEvent;
use App\Events\V2\Router\BeforeDeleteEvent;
use App\Events\V2\Router\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateRouterRequest;
use App\Http\Requests\V2\UpdateRouterRequest;
use App\Models\V2\Gateway;
use App\Models\V2\Router;
use App\Resources\V2\RouterResource;
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
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
     * @param \App\Http\Requests\V2\CreateRouterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRouterRequest $request)
    {
        event(new BeforeCreateEvent());
        $router = new Router($request->only(['name', 'vpc_id']));
        $router->save();
        $router->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateRouterRequest $request
     * @param string $routerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRouterRequest $request, string $routerId)
    {
        event(new BeforeUpdateEvent());
        $router = Router::forUser(app('request')->user)->findOrFail($routerId);
        $router->fill($request->only(['name', 'vpc_id']));
        $router->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $routerId)
    {
        event(new BeforeDeleteEvent());
        Router::forUser($request->user)->findOrFail($routerId)->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }

    /**
     * Associate a gateway with a router
     * @param \Illuminate\Http\Request $request
     * @param string $routerId
     * @param string $gatewayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function gatewaysCreate(Request $request, string $routerId, string $gatewayId)
    {
        $router = Router::forUser($request->user)->findOrFail($routerId);
        $gateway = Gateway::findOrFail($gatewayId);
        $router->gateways()->attach($gateway->id);
        return response()->json([], 204);
    }

    /**
     * Remove Association between Gateway and Router
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @param string $gatewaysUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function gatewaysDestroy(Request $request, string $routerUuid, string $gatewaysUuid)
    {
        $router = Router::forUser($request->user)->findOrFail($routerUuid);
        $gateway = Gateway::findOrFail($gatewaysUuid);
        $router->gateways()->detach($gateway->id);
        return response()->json([], 204);
    }
}

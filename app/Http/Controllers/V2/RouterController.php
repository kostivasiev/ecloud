<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Events\V2\RouterAvailabilityZoneDetach;
use App\Http\Requests\V2\CreateRouterRequest;
use App\Http\Requests\V2\UpdateRouterRequest;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use App\Models\V2\Router;
use App\Resources\V2\AvailabilityZoneResource;
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
        $router = new Router($request->only(['name', 'vpc_id']));
        $router->save();
        $router->refresh();
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateRouterRequest $request
     * @param string $routerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRouterRequest $request, string $routerId)
    {
        $router = Router::forUser(app('request')->user)->findOrFail($routerId);
        $router->fill($request->only(['name', 'vpc_id']));
        $router->save();
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $routerId)
    {
        Router::forUser($request->user)->findOrFail($routerId)->delete();
        return response()->json([], 204);
    }

    public function availabilityZones(Request $request, string $routerId, QueryTransformer $queryTransformer)
    {
        $collection = Router::forUser($request->user)->findOrFail($routerId)->availabilityZones()->query();
        $queryTransformer->config(AvailabilityZone::class)->transform($collection);
        return AvailabilityZoneResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function availabilityZonesAttach(Request $request, string $routerId, string $availabilityZonesId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($availabilityZonesId);
        $router = Router::forUser($request->user)->findOrFail($routerId);
        $router->avilabilityZones()->attach($availabilityZone);
        event(new RouterAvailabilityZoneAttach($router, $availabilityZone));
        return response()->json([], 204);
    }

    public function availabilityZonesDetach(Request $request, string $routerUuid, string $availabilityZonesId)
    {
        $availabilityZone = AvailabilityZone::findOrFail($availabilityZonesId);
        $router = Router::forUser($request->user)->findOrFail($routerUuid);
        $router->avilabilityZones()->detach($availabilityZone);
        event(new RouterAvailabilityZoneDetach($router, $availabilityZone));
        return response()->json([], 204);
    }

    /**
     * Associate a gateway with a router
     * @param \Illuminate\Http\Request $request
     * @param string $routerId
     * @param string $gatewayId
     * @return \Illuminate\Http\JsonResponse
     */
    public function gatewaysAttach(Request $request, string $routerId, string $gatewayId)
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
    public function gatewaysDetach(Request $request, string $routerUuid, string $gatewaysUuid)
    {
        $router = Router::forUser($request->user)->findOrFail($routerUuid);
        $gateway = Gateway::findOrFail($gatewaysUuid);
        $router->gateways()->detach($gateway->id);
        return response()->json([], 204);
    }
}

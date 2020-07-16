<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Routers\AfterCreateEvent;
use App\Events\V2\Routers\AfterDeleteEvent;
use App\Events\V2\Routers\AfterUpdateEvent;
use App\Events\V2\Routers\BeforeCreateEvent;
use App\Events\V2\Routers\BeforeDeleteEvent;
use App\Events\V2\Routers\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateRoutersRequest;
use App\Http\Requests\V2\UpdateRoutersRequest;
use App\Models\V2\Gateways;
use App\Models\V2\Routers;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class RoutersController
 * @package App\Http\Controllers\V2
 */
class RoutersController extends BaseController
{
    /**
     * Get availability zones collection
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = Routers::query();

        (new QueryTransformer($request))
            ->config(Routers::class)
            ->transform($collectionQuery);

        $routers = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $routers,
            200
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $routerUuid)
    {
        return $this->respondItem(
            $request,
            Routers::findOrFail($routerUuid),
            200
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateRoutersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRoutersRequest $request)
    {
        event(new BeforeCreateEvent());
        $router = new Routers($request->only(['name']));
        $router->save();
        $router->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $router->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateRoutersRequest $request
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoutersRequest $request, string $routerUuid)
    {
        event(new BeforeUpdateEvent());
        $router = Routers::findOrFail($routerUuid);
        $router->fill($request->only(['name']));
        $router->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $router->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $routerUuid)
    {
        event(new BeforeDeleteEvent());
        $router = Routers::findOrFail($routerUuid);
        $router->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }

    /**
     * Associate a gateway with a router
     * @param \Illuminate\Http\Request $request
     * @param string $routerUuid
     * @param string $gatewaysUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function gatewaysCreate(Request $request, string $routerUuid, string $gatewaysUuid)
    {
        $router = Routers::findOrFail($routerUuid);
        $gateway = Gateways::findOrFail($gatewaysUuid);
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
        $router = Routers::findOrFail($routerUuid);
        $gateway = Gateways::findOrFail($gatewaysUuid);
        $router->gateways()->detach($gateway->id);
        return response()->json([], 204);
    }
}

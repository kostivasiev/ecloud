<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Gateways\AfterCreateEvent;
use App\Events\V2\Gateways\AfterDeleteEvent;
use App\Events\V2\Gateways\AfterUpdateEvent;
use App\Events\V2\Gateways\BeforeCreateEvent;
use App\Events\V2\Gateways\BeforeDeleteEvent;
use App\Events\V2\Gateways\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateGatewaysRequest;
use App\Http\Requests\V2\UpdateGatewaysRequest;
use App\Models\V2\Gateways;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class GatewaysController
 * @package App\Http\Controllers\V2
 */
class GatewaysController extends BaseController
{
    /**
     * Get availability zones collection
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = Gateways::query();

        (new QueryTransformer($request))
            ->config(Gateways::class)
            ->transform($collectionQuery);

        $gateways = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $gateways,
            200
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $gatewayUuid
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $gatewayUuid)
    {
        return $this->respondItem(
            $request,
            Gateways::findOrFail($gatewayUuid),
            200
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateGatewaysRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateGatewaysRequest $request)
    {
        event(new BeforeCreateEvent());
        $gateway = new Gateways($request->only(['name']));
        $gateway->save();
        $gateway->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $gateway->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateGatewaysRequest $request
     * @param string $gatewayUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateGatewaysRequest $request, string $gatewayUuid)
    {
        event(new BeforeUpdateEvent());
        $gateway = Gateways::findOrFail($gatewayUuid);
        $gateway->fill($request->only(['name']));
        $gateway->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $gateway->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $gatewayUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $gatewayUuid)
    {
        event(new BeforeDeleteEvent());
        $gateway = Gateways::findOrFail($gatewayUuid);
        $gateway->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

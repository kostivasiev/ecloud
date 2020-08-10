<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateGatewayRequest;
use App\Http\Requests\V2\UpdateGatewayRequest;
use App\Models\V2\Gateway;
use App\Resources\V2\GatewayResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class GatewayController
 * @package App\Http\Controllers\V2
 */
class GatewayController extends BaseController
{
    /**
     * Get gateway collection
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Gateway::query();

        (new QueryTransformer($request))
            ->config(Gateway::class)
            ->transform($collection);

        return GatewayResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $gatewayUuid
     * @return GatewayResource
     */
    public function show(Request $request, string $gatewayUuid)
    {
        return new GatewayResource(
            Gateway::findOrFail($gatewayUuid)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateGatewayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateGatewayRequest $request)
    {
        $gateway = new Gateway($request->only(['name', 'availability_zone_id']));
        $gateway->save();
        $gateway->refresh();
        return $this->responseIdMeta($request, $gateway->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateGatewayRequest $request
     * @param string $gatewayUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateGatewayRequest $request, string $gatewayUuid)
    {
        $gateway = Gateway::findOrFail($gatewayUuid);
        $gateway->fill($request->only(['name', 'availability_zone_id']));
        $gateway->save();
        return $this->responseIdMeta($request, $gateway->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $gatewayUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $gatewayUuid)
    {
        $gateway = Gateway::findOrFail($gatewayUuid);
        $gateway->delete();
        return response()->json([], 204);
    }
}

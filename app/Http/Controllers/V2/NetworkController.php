<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Network\CreateRequest;
use App\Http\Requests\V2\Network\UpdateRequest;
use App\Models\V2\Network;
use App\Resources\V2\NetworkResource;
use App\Resources\V2\NicResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class NetworkController
 * @package App\Http\Controllers\V2
 */
class NetworkController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Network::forUser($request->user);
        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return NetworkResource
     */
    public function show(Request $request, string $networkId)
    {
        return new NetworkResource(
            Network::forUser($request->user)->findOrFail($networkId)
        );
    }

    /**
     * @param CreateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $network = new Network($request->only([
            'router_id',
            'name',
            'subnet',
        ]));
        $network->save();
        $network->refresh();
        return $this->responseIdMeta($request, $network->getKey(), 201);
    }

    /**
     * @param UpdateRequest  $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $networkId)
    {
        $network = Network::forUser(app('request')->user)->findOrFail($networkId);
        $network->fill($request->only([
            'router_id',
            'name',
            'subnet',
        ]));
        $network->save();
        return $this->responseIdMeta($request, $network->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkId)
    {
        $network = Network::forUser($request->user)->findOrFail($networkId);
        $network->delete();
        return response()->json([], 204);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $zoneId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\HigherOrderTapProxy|mixed
     */
    public function nics(Request $request, string $zoneId)
    {
        return NicResource::collection(
            Network::forUser($request->user)
                ->findOrFail($zoneId)
                ->nics()
                ->paginate($request->input('per_page', env('PAGINATION_LIMIT')))
        );
    }
}

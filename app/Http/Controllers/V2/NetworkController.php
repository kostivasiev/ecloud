<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateNetworkRequest;
use App\Http\Requests\V2\UpdateNetworkRequest;
use App\Models\V2\Network;
use App\Resources\V2\NetworkResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class NetworkController
 * @package App\Http\Controllers\V2
 */
class NetworkController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Network::query();

        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $networkId
     * @return \App\Resources\V2\NetworkResource
     */
    public function show(string $networkId)
    {
        return new NetworkResource(
            Network::findOrFail($networkId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateNetworkRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateNetworkRequest $request)
    {
        $network = new Network($request->only([
            'router_id', 'availability_zone_id',  'name'
        ]));
        $network->save();
        $network->refresh();
        return $this->responseIdMeta($request, $network->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateNetworkRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNetworkRequest $request, string $networkId)
    {
        $networks = Network::findOrFail($networkId);
        $networks->fill($request->only([
            'router_id', 'availability_zone_id',  'name'
        ]));
        $networks->save();
        return $this->responseIdMeta($request, $networks->getKey(), 200);
    }

    /**
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $networkId)
    {
        $networks = Network::findOrFail($networkId);
        $networks->delete();
        return response()->json([], 204);
    }
}

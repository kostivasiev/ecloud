<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Network\AfterCreateEvent;
use App\Events\V2\Network\AfterDeleteEvent;
use App\Events\V2\Network\AfterUpdateEvent;
use App\Events\V2\Network\BeforeCreateEvent;
use App\Events\V2\Network\BeforeDeleteEvent;
use App\Events\V2\Network\BeforeUpdateEvent;
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
     * @return \App\Resources\V2\NetworkResource
     */
    public function show(Request $request, string $networkId)
    {
        return new NetworkResource(
            Network::forUser($request->user)->findOrFail($networkId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateNetworkRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateNetworkRequest $request)
    {
        event(new BeforeCreateEvent());
        $network = new Network($request->only([
            'router_id', 'availability_zone_id',  'name'
        ]));
        $network->save();
        $network->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $network->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateNetworkRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNetworkRequest $request, string $networkId)
    {
        event(new BeforeUpdateEvent());
        $network = Network::forUser(app('request')->user)->findOrFail($networkId);
        $network->fill($request->only([
            'router_id', 'availability_zone_id',  'name'
        ]));
        $network->save();
        event(new AfterUpdateEvent());
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
        event(new BeforeDeleteEvent());
        $network = Network::forUser($request->user)->findOrFail($networkId);
        $network->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

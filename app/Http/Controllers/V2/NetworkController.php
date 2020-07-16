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
        event(new BeforeCreateEvent());
        $networks = new Network($request->only([
            'code', 'name',
        ]));
        $networks->save();
        $networks->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $networks->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateNetworkRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNetworkRequest $request, string $networkId)
    {
        event(new BeforeUpdateEvent());
        $networks = Network::findOrFail($networkId);
        $networks->fill($request->only([
            'code', 'name',
        ]));
        $networks->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $networks->getKey(), 200);
    }

    /**
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $networkId)
    {
        event(new BeforeDeleteEvent());
        $networks = Network::findOrFail($networkId);
        $networks->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

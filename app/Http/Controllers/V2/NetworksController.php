<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Networks\AfterCreateEvent;
use App\Events\V2\Networks\AfterDeleteEvent;
use App\Events\V2\Networks\AfterUpdateEvent;
use App\Events\V2\Networks\BeforeCreateEvent;
use App\Events\V2\Networks\BeforeDeleteEvent;
use App\Events\V2\Networks\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateNetworksRequest;
use App\Http\Requests\V2\UpdateNetworksRequest;
use App\Models\V2\Networks;
use App\Resources\V2\NetworksResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class NetworksController
 * @package App\Http\Controllers\V2
 */
class NetworksController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Networks::query();

        $queryTransformer->config(Networks::class)
            ->transform($collection);

        return NetworksResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $networkId
     * @return \App\Resources\V2\NetworksResource
     */
    public function show(string $networkId)
    {
        return new NetworksResource(
            Networks::findOrFail($networkId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateNetworksRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateNetworksRequest $request)
    {
        event(new BeforeCreateEvent());
        $networks = new Networks($request->only([
            'code', 'name',
        ]));
        $networks->save();
        $networks->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $networks->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateNetworksRequest $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNetworksRequest $request, string $networkId)
    {
        event(new BeforeUpdateEvent());
        $networks = Networks::findOrFail($networkId);
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
        $networks = Networks::findOrFail($networkId);
        $networks->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

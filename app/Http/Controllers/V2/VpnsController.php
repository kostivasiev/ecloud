<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Vpns\AfterCreateEvent;
use App\Events\V2\Vpns\AfterDeleteEvent;
use App\Events\V2\Vpns\AfterUpdateEvent;
use App\Events\V2\Vpns\BeforeCreateEvent;
use App\Events\V2\Vpns\BeforeDeleteEvent;
use App\Events\V2\Vpns\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateVpnsRequest;
use App\Http\Requests\V2\UpdateVpnsRequest;
use App\Models\V2\Vpns;
use App\Resources\V2\VpnsResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VpnsController
 * @package App\Http\Controllers\V2
 */
class VpnsController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Vpns::query();

        $queryTransformer->config(Vpns::class)
            ->transform($collection);

        return VpnsResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $vpnId
     * @return \App\Resources\V2\VpnsResource
     */
    public function show(string $vpnId)
    {
        return new VpnsResource(
            Vpns::findOrFail($vpnId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVpnsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpnsRequest $request)
    {
        event(new BeforeCreateEvent());
        $vpns = new Vpns($request->only(['router_id', 'availability_zone_id']));
        $vpns->save();
        $vpns->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $vpns->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVpnsRequest $request
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpnsRequest $request, string $vpnId)
    {
        event(new BeforeUpdateEvent());
        $vpns = Vpns::findOrFail($vpnId);
        $vpns->fill($request->only(['router_id', 'availability_zone_id']));
        $vpns->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $vpns->getKey(), 200);
    }

    /**
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $vpnId)
    {
        event(new BeforeDeleteEvent());
        $vpns = Vpns::findOrFail($vpnId);
        $vpns->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

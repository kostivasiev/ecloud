<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Dhcps\AfterCreateEvent;
use App\Events\V2\Dhcps\AfterDeleteEvent;
use App\Events\V2\Dhcps\AfterUpdateEvent;
use App\Events\V2\Dhcps\BeforeCreateEvent;
use App\Events\V2\Dhcps\BeforeDeleteEvent;
use App\Events\V2\Dhcps\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateDhcpsRequest;
use App\Http\Requests\V2\UpdateDhcpsRequest;
use App\Models\V2\Dhcps;
use App\Resources\V2\DhcpsResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class DhcpsController
 * @package App\Http\Controllers\V2
 */
class DhcpsController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Dhcps::query();

        $queryTransformer->config(Dhcps::class)
            ->transform($collection);

        return DhcpsResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $dhcpId
     * @return \App\Resources\V2\DhcpsResource
     */
    public function show(string $dhcpId)
    {
        return new DhcpsResource(
            Dhcps::findOrFail($dhcpId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateDhcpsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateDhcpsRequest $request)
    {
        event(new BeforeCreateEvent());
        $dhcps = new Dhcps($request->only(['vpc_id']));
        $dhcps->save();
        $dhcps->refresh();
        event(new AfterCreateEvent());
        return $this->responseIdMeta($request, $dhcps->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateDhcpsRequest $request
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDhcpsRequest $request, string $dhcpId)
    {
        event(new BeforeUpdateEvent());
        $dhcp = Dhcps::findOrFail($dhcpId);
        $dhcp->fill($request->only(['vpc_id']));
        $dhcp->save();
        event(new AfterUpdateEvent());
        return $this->responseIdMeta($request, $dhcp->getKey(), 200);
    }

    /**
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $dhcpId)
    {
        event(new BeforeDeleteEvent());
        $dhcp = Dhcps::findOrFail($dhcpId);
        $dhcp->delete();
        event(new AfterDeleteEvent());
        return response()->json([], 204);
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Events\V2\Dhcp\AfterCreateEvent;
use App\Events\V2\Dhcp\AfterDeleteEvent;
use App\Events\V2\Dhcp\AfterUpdateEvent;
use App\Events\V2\Dhcp\BeforeCreateEvent;
use App\Events\V2\Dhcp\BeforeDeleteEvent;
use App\Events\V2\Dhcp\BeforeUpdateEvent;
use App\Http\Requests\V2\CreateDhcpRequest;
use App\Http\Requests\V2\UpdateDhcpRequest;
use App\Models\V2\Dhcp;
use App\Resources\V2\DhcpResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class DhcpController
 * @package App\Http\Controllers\V2
 */
class DhcpController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Dhcp::query();

        $queryTransformer->config(Dhcp::class)
            ->transform($collection);

        return DhcpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $dhcpId
     * @return \App\Resources\V2\DhcpResource
     */
    public function show(string $dhcpId)
    {
        return new DhcpResource(
            Dhcp::findOrFail($dhcpId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateDhcpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateDhcpRequest $request)
    {
        $dhcps = new Dhcp($request->only(['vpc_id']));
        $dhcps->save();
        $dhcps->refresh();
        return $this->responseIdMeta($request, $dhcps->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateDhcpRequest $request
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDhcpRequest $request, string $dhcpId)
    {
        $dhcp = Dhcp::findOrFail($dhcpId);
        $dhcp->fill($request->only(['vpc_id']));
        $dhcp->save();
        return $this->responseIdMeta($request, $dhcp->getKey(), 200);
    }

    /**
     * @param string $dhcpId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $dhcpId)
    {
        $dhcp = Dhcp::findOrFail($dhcpId);
        $dhcp->delete();
        return response()->json([], 204);
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateVpnRequest;
use App\Http\Requests\V2\UpdateVpnRequest;
use App\Models\V2\Vpn;
use App\Resources\V2\VpnResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class VpnController
 * @package App\Http\Controllers\V2
 */
class VpnController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Vpn::query();

        $queryTransformer->config(Vpn::class)
            ->transform($collection);

        return VpnResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param string $vpnId
     * @return \App\Resources\V2\VpnResource
     */
    public function show(string $vpnId)
    {
        return new VpnResource(
            Vpn::findOrFail($vpnId)
        );
    }

    /**
     * @param \App\Http\Requests\V2\CreateVpnRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpnRequest $request)
    {
        $vpns = new Vpn($request->only(['router_id', 'availability_zone_id']));
        $vpns->save();
        $vpns->refresh();
        return $this->responseIdMeta($request, $vpns->getKey(), 201);
    }

    /**
     * @param \App\Http\Requests\V2\UpdateVpnRequest $request
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpnRequest $request, string $vpnId)
    {
        $vpns = Vpn::findOrFail($vpnId);
        $vpns->fill($request->only(['router_id', 'availability_zone_id']));
        $vpns->save();
        return $this->responseIdMeta($request, $vpns->getKey(), 200);
    }

    /**
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $vpnId)
    {
        $vpns = Vpn::findOrFail($vpnId);
        $vpns->delete();
        return response()->json([], 204);
    }
}

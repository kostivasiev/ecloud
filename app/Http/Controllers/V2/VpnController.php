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
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Vpn::forUser($request->user());

        $queryTransformer->config(Vpn::class)
            ->transform($collection);

        return VpnResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $vpnId
     * @return VpnResource
     */
    public function show(Request $request, string $vpnId)
    {
        return new VpnResource(
            Vpn::forUser($request->user())->findOrFail($vpnId)
        );
    }

    /**
     * @param CreateVpnRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateVpnRequest $request)
    {
        $vpns = new Vpn($request->only(['router_id']));
        $vpns->save();
        $vpns->refresh();
        return $this->responseIdMeta($request, $vpns->getKey(), 201);
    }

    /**
     * @param UpdateVpnRequest $request
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVpnRequest $request, string $vpnId)
    {
        $vpns = Vpn::forUser(app('request')->user())->findOrFail($vpnId);
        $vpns->fill($request->only(['router_id']));
        $vpns->save();
        return $this->responseIdMeta($request, $vpns->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $vpnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $vpnId)
    {
        Vpn::forUser($request->user())->findOrFail($vpnId)->delete();
        return response()->json([], 204);
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateVpnRequest;
use App\Http\Requests\V2\UpdateVpnRequest;
use App\Models\V2\LocalEndpoint;
use App\Models\V2\Vpn;
use App\Resources\V2\LocalEndpointResource;
use App\Resources\V2\VpnResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;
use UKFast\Responses\UKFastResource;

class VpnController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Vpn::forUser($request->user());

        $queryTransformer->config(Vpn::class)
            ->transform($collection);

        return VpnResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnId)
    {
        return new VpnResource(
            Vpn::forUser($request->user())->findOrFail($vpnId)
        );
    }

    public function create(CreateVpnRequest $request)
    {
        $vpns = new Vpn($request->only(['router_id']));
        $vpns->save();
        $vpns->refresh();
        return $this->responseIdMeta($request, $vpns->id, 201);
    }

    public function update(UpdateVpnRequest $request, string $vpnId)
    {
        $vpns = Vpn::forUser(Auth::user())->findOrFail($vpnId);
        $vpns->fill($request->only(['router_id']));
        $vpns->save();
        return $this->responseIdMeta($request, $vpns->id, 200);
    }

    public function destroy(Request $request, string $vpnId)
    {
        Vpn::forUser($request->user())->findOrFail($vpnId)->delete();
        return response('', 204);
    }

    public function localEndpoint(Request $request, QueryTransformer $queryTransformer, string $vpnId)
    {
        $collection = Vpn::forUser($request->user())->findOrFail($vpnId)->localEndpoints();
        $queryTransformer->config(LocalEndpoint::class)
            ->transform($collection);

        return LocalEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnService\Create;
use App\Http\Requests\V2\VpnService\Update;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Resources\V2\VpnEndpointResource;
use App\Resources\V2\VpnServiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnServiceController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = VpnService::forUser($request->user());

        $queryTransformer->config(VpnService::class)
            ->transform($collection);

        return VpnServiceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnServiceId)
    {
        return new VpnServiceResource(
            VpnService::forUser($request->user())->findOrFail($vpnServiceId)
        );
    }

    public function create(Create $request)
    {
        $vpnService = new VpnService($request->only(['router_id']));
        $vpnService->save();
        $vpnService->refresh();
        return $this->responseIdMeta($request, $vpnService->id, 201);
    }

    public function update(Update $request, string $vpnServiceId)
    {
        $vpnService = VpnService::forUser(Auth::user())->findOrFail($vpnServiceId);
        $vpnService->fill($request->only(['router_id']));
        $vpnService->save();
        return $this->responseIdMeta($request, $vpnService->id, 200);
    }

    public function destroy(Request $request, string $vpnServiceId)
    {
        VpnService::forUser($request->user())->findOrFail($vpnServiceId)->delete();
        return response('', 204);
    }

    public function endpoints(Request $request, QueryTransformer $queryTransformer, string $vpnServiceId)
    {
        $collection = VpnService::forUser($request->user())->findOrFail($vpnServiceId)->vpnEndpoints();
        $queryTransformer->config(VpnEndpoint::class)
            ->transform($collection);

        return VpnEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

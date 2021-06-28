<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnSession\Create;
use App\Http\Requests\V2\VpnSession\Update;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Resources\V2\VpnEndpointResource;
use App\Resources\V2\VpnServiceResource;
use App\Resources\V2\VpnSessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnSessionController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = VpnSession::forUser($request->user());
        $queryTransformer->config(VpnSession::class)
            ->transform($collection);

        return VpnSessionResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnSessionId)
    {
        return new VpnSessionResource(
            VpnSession::forUser($request->user())->findOrFail($vpnSessionId)
        );
    }

    public function create(Create $request)
    {
        $vpnSession = new VpnSession($request->only([
            'id',
            'name',
            'vpn_profile_group_id',
            'remote_ip',
            'remote_networks',
            'local_networks',
        ]));
        $vpnSession->save();

        $vpnSession->vpnEndpoints()->attach($request->get('vpn_endpoint_id'));
        $vpnSession->vpnServices()->attach($request->get('vpn_service_id'));
        $vpnSession->refresh();

        return $this->responseIdMeta($request, $vpnSession->id, 202);
    }

    public function update(Update $request, string $vpnSessionId)
    {
        $vpnSession = VpnSession::forUser(Auth::user())->findOrFail($vpnSessionId);
        $vpnSession->fill($request->only([
            'id',
            'name',
            'vpn_profile_group_id',
            'remote_ip',
            'remote_networks',
            'local_networks',
        ]));
        $vpnSession->save();

        $vpnSession->vpnEndpoints()->attach($request->get('vpn_endpoint_id'));
        $vpnSession->vpnServices()->attach($request->get('vpn_service_id'));
        $vpnSession->refresh();

        return $this->responseIdMeta($request, $vpnSession->id, 202);
    }

    public function destroy(Request $request, string $vpnSessionId)
    {
        VpnSession::forUser($request->user())->findOrFail($vpnSessionId)->delete();
        return response('', 204);
    }

    public function endpoints(Request $request, QueryTransformer $queryTransformer, string $vpnSessionId)
    {
        $collection = VpnSession::forUser($request->user())->findOrFail($vpnSessionId)->vpnEndpoints();
        $queryTransformer->config(VpnEndpoint::class)
            ->transform($collection);

        return VpnEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function services(Request $request, QueryTransformer $queryTransformer, string $vpnSessionId)
    {
        $collection = VpnSession::forUser($request->user())->findOrFail($vpnSessionId)->vpnServices();
        $queryTransformer->config(VpnService::class)
            ->transform($collection);

        return VpnServiceResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

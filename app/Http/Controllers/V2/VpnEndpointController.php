<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnEndpoint\CreateRequest;
use App\Http\Requests\V2\VpnEndpoint\UpdateRequest;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Resources\V2\VpnEndpointResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnEndpointController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VpnEndpoint::forUser($request->user());
        (new QueryTransformer($request))
            ->config(VpnEndpoint::class)
            ->transform($collection);

        return VpnEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnEndpointId)
    {
        return new VpnEndpointResource(
            VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId)
        );
    }

    public function store(CreateRequest $request)
    {
        $vpnEndpoint = new VpnEndpoint(
            $request->only(['name', 'floating_ip_id'])
        );
        $vpnEndpoint->save();
        $vpnEndpoint->vpnServices()->sync([$request->get('vpn_service_id')]);
        $vpnEndpoint->refresh();
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202);
    }

    public function update(UpdateRequest $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser(Auth::user())->findOrFail($vpnEndpointId);
        $vpnEndpoint->fill($request->only(['name', 'floating_ip_id']));
        $vpnEndpoint->save();
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202);
    }

    public function destroy(Request $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId);
        $vpnEndpoint->vpnServices()->detach();
        $vpnEndpoint->delete();
        return response('', 204);
    }
}

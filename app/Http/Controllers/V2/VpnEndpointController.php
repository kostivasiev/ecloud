<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnEndpoint\Create;
use App\Http\Requests\V2\VpnEndpoint\Update;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\Vpn;
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

    public function store(Create $request)
    {
        $localEndpoint = new VpnEndpoint(
            $request->only(['name', 'vpn_id', 'fip_id'])
        );
        // if no fip_id supplied then create one
        if (!$request->has('fip_id')) {
            $vpn = Vpn::forUser($request->user())->findOrFail($request->get('vpn_id'));
            $floatingIp = app()->make(FloatingIp::class, [
                'attributes' => [
                    'vpc_id' => $vpn->router->vpc_id,
                ]
            ]);
            $floatingIp->save();
            $localEndpoint->fip_id = $floatingIp->id;
        }
        $localEndpoint->save();
        return $this->responseIdMeta($request, $localEndpoint->id, 202);
    }

    public function update(Update $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser(Auth::user())->findOrFail($vpnEndpointId);
        $vpnEndpoint->fill($request->only(['name', 'vpn_id', 'fip_id']));
        $vpnEndpoint->save();
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202);
    }

    public function destroy(Request $request, string $vpnEndpointId)
    {
        VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId)->delete();
        return response('', 204);
    }
}

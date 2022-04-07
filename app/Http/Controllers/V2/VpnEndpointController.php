<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnEndpoint\CreateRequest;
use App\Http\Requests\V2\VpnEndpoint\UpdateRequest;
use App\Models\V2\VpnEndpoint;
use App\Resources\V2\VpnEndpointResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpnEndpointController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VpnEndpoint::forUser($request->user());

        return VpnEndpointResource::collection($collection->search()->paginate(
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
            $request->only(['name', 'vpn_service_id'])
        );
        $vpnEndpoint->save();
        $task = $vpnEndpoint->syncSave([
            'floating_ip_id' => $request->get('floating_ip_id'),
        ]);
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser(Auth::user())->findOrFail($vpnEndpointId);
        $vpnEndpoint->fill($request->only(['name']));
        $task = $vpnEndpoint->syncSave();
        return $this->responseIdMeta($request, $vpnEndpoint->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpnEndpointId)
    {
        $vpnEndpoint = VpnEndpoint::forUser($request->user())->findOrFail($vpnEndpointId);
        $task = $vpnEndpoint->syncDelete();
        return $this->responseTaskId($task->id);
    }
}

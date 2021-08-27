<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnSession\CreateRequest;
use App\Http\Requests\V2\VpnSession\UpdateRequest;
use App\Models\V2\Credential;
use App\Models\V2\VpnSession;
use App\Resources\V2\CredentialResource;
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

    public function create(CreateRequest $request)
    {
        $vpnSession = new VpnSession($request->only([
            'id',
            'name',
            'vpn_profile_group_id',
            'vpn_service_id',
            'vpn_endpoint_id',
            'remote_ip',
            'remote_networks',
            'local_networks',
        ]));
        $task = $vpnSession->syncSave();

        return $this->responseIdMeta($request, $vpnSession->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $vpnSessionId)
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
        $task = $vpnSession->syncSave();

        return $this->responseIdMeta($request, $vpnSession->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpnSessionId)
    {
        $vpnSession = VpnSession::forUser($request->user())->findOrFail($vpnSessionId);
        $task = $vpnSession->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function credentials(Request $request, QueryTransformer $queryTransformer, string $vpnSessionId)
    {
        $collection = VpnSession::forUser($request->user())->findOrFail($vpnSessionId)
            ->credentials();

        $queryTransformer->config(Credential::class)
            ->transform($collection);

        return CredentialResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}

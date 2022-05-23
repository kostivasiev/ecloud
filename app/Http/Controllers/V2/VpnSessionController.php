<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnSession\CreateRequest;
use App\Http\Requests\V2\VpnSession\UpdateKeyRequest;
use App\Http\Requests\V2\VpnSession\UpdateRequest;
use App\Models\V2\Credential;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Resources\V2\VpnSessionResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UKFast\Api\Exceptions\NotFoundException;

class VpnSessionController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VpnSession::forUser($request->user());
        return VpnSessionResource::collection($collection->search()->paginate(
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
        ]));

        $task = $vpnSession->withTaskLock(function ($vpnSession) use ($request) {
            $vpnSession->save();

            // do this here rather than place the key in task data
            if ($request->has('psk')) {
                $credential = $vpnSession?->credentials()
                    ->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)
                    ->first();
                if (!$credential) {
                    $credential = new Credential(
                        [
                            'name' => 'Pre-shared Key for VPN Session ' . $vpnSession->id,
                            'host' => null,
                            'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                            'password' => $request->input('psk'),
                            'port' => null,
                            'is_hidden' => true,
                        ]
                    );
                } else {
                    $credential->password = $request->input('psk');
                }
                $vpnSession->credentials()->save($credential);
            }

            foreach (Str::of($request->get('local_networks'))->explode(',') as $localNetwork) {
                $vpnSession->vpnSessionNetworks()->create([
                    'type' => VpnSessionNetwork::TYPE_LOCAL,
                    'ip_address' => (string) Str::of($localNetwork)->trim(),
                ]);
            }

            foreach (Str::of($request->get('remote_networks'))->explode(',') as $remoteNetwork) {
                $vpnSession->vpnSessionNetworks()->create([
                    'type' => VpnSessionNetwork::TYPE_REMOTE,
                    'ip_address' => (string) Str::of($remoteNetwork)->trim(),
                ]);
            }

            return $vpnSession->syncSave();
        });

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
        ]));

        $task = $vpnSession->withTaskLock(function ($vpnSession) use ($request) {
            $vpnSession->save();

            if ($request->filled('local_networks')) {
                $vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->each(function ($network) {
                    $network->delete();
                });

                foreach (Str::of($request->get('local_networks'))->explode(',') as $localNetwork) {
                    $vpnSession->vpnSessionNetworks()->create([
                        'type' => VpnSessionNetwork::TYPE_LOCAL,
                        'ip_address' => (string) Str::of($localNetwork)->trim(),
                    ]);
                }
            }

            if ($request->filled('remote_networks')) {
                $vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->each(function ($network) {
                    $network->delete();
                });

                foreach (Str::of($request->get('remote_networks'))->explode(',') as $remoteNetwork) {
                    $vpnSession->vpnSessionNetworks()->create([
                        'type' => VpnSessionNetwork::TYPE_REMOTE,
                        'ip_address' => (string)Str::of($remoteNetwork)->trim(),
                    ]);
                }
            }

            return $vpnSession->createSync(Sync::TYPE_UPDATE);
        });

        return $this->responseIdMeta($request, $vpnSession->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpnSessionId)
    {
        $vpnSession = VpnSession::forUser($request->user())->findOrFail($vpnSessionId);
        $task = $vpnSession->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function preSharedKey(Request $request, string $vpnSessionId)
    {
        $vpnSession = VpnSession::forUser($request->user())->findOrFail($vpnSessionId);

        $credentialQuery = $vpnSession->credentials()->where('username', VpnSession::CREDENTIAL_PSK_USERNAME);
        if (!$credentialQuery->exists()) {
            throw new NotFoundException(
                'Unable to load pre-shared key for VPN session'
            );
        }

        return response()->json(
            [
                'data' => [
                    'psk' => $credentialQuery->get()->first()->password
                ],
                'meta' => (object)[]
            ]
        );
    }

    public function updatePreSharedKey(UpdateKeyRequest $request, string $vpnSessionId)
    {
        $vpnSession = VpnSession::forUser($request->user())->findOrFail($vpnSessionId);

        $credential = $vpnSession->credentials()
            ->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)
            ->firstOrFail();
        $credential->update([
            'password' => $request->input('psk'),
        ]);
        $vpnSession->syncSave();

        return response('', 204);
    }
}

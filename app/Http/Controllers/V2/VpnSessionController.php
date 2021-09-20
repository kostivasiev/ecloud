<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnSession\CreateRequest;
use App\Http\Requests\V2\VpnSession\UpdateRequest;
use App\Models\V2\Credential;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Resources\V2\CredentialResource;
use App\Resources\V2\VpnSessionResource;
use App\Support\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UKFast\Api\Exceptions\NotFoundException;
use UKFast\DB\Ditto\QueryTransformer;

class VpnSessionController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        if ($request->hasAny([
            'vpc_id',
            'vpc_id:eq', 'vpc_id:in', 'vpc_id:lk',
            'vpc_id:neq', 'vpc_id:nin', 'vpc_id:nlk',
        ])) {
            $vpnSessionIds = VpnSession::forUser($request->user())->get();

            if ($request->has('vpc_id') || $request->has('vpc_id:eq')) {
                if ($request->has('vpc_id')) {
                    $vpcId = $request->get('vpc_id');
                    $request->query->remove('vpc_id');
                } else {
                    $vpcId = $request->get('vpc_id:eq');
                    $request->query->remove('vpc_id:eq');
                }

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->vpnService->router || $vpnService->vpnService->router->vpc->id != $vpcId;
                });
            }

            if ($request->has('vpc_id:neq')) {
                $vpcId = $request->get('vpc_id:neq');
                $request->query->remove('vpc_id:neq');

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->vpnService->router || $vpnService->vpnService->router->vpc->id == $vpcId;
                });
            }

            if ($request->has('vpc_id:lk')) {
                $vpcId = $request->get('vpc_id:lk');
                $request->query->remove('vpc_id:lk');

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnService->vpnService->router->vpc->id
                        ) === 0;
                });
            }

            if ($request->has('vpc_id:nlk')) {
                $vpcId = $request->get('vpc_id:nlk');
                $request->query->remove('vpc_id:nlk');

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($vpcId) {
                    return !$vpnService->vpnService->router
                        || preg_match(
                            '/' . str_replace('\*', '\S*', preg_quote($vpcId)) . '/',
                            $vpnService->vpnService->router->vpc->id
                        ) === 1;
                });
            }

            if ($request->has('vpc_id:in')) {
                $ids = explode(',', $request->get('vpc_id:in'));
                $request->query->remove('vpc_id:in');

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($ids) {
                    return !$vpnService->vpnService->router || !in_array($vpnService->vpnService->router->vpc->id, $ids);
                });
            }

            if ($request->has('vpc_id:nin')) {
                $ids = explode(',', $request->get('vpc_id:nin'));
                $request->query->remove('vpc_id:nin');

                $vpnSessionIds = $vpnSessionIds->reject(function ($vpnService) use ($ids) {
                    return !$vpnService->vpnService->router || in_array($vpnService->vpnService->router->vpc->id, $ids);
                });
            }

            $collection = VpnSession::whereIn('id', $vpnSessionIds->map(function ($vpnService) {
                return $vpnService->id;
            }));
        } else {
            $collection = VpnSession::forUser($request->user());
        }

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
        ]));
        $task = $vpnSession->withTaskLock(function ($vpnSession) use ($request) {
            $vpnSession->save();

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
}

<?php
namespace App\Jobs\Nsx\VpnSession;

use App\Jobs\Job;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Str;
use IPLib\Range\Subnet;

class CreateVpnSession extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    /**
     * See: https://185.197.63.88/policy/api_includes/method_CreateOrPatchTier1IPSecVpnSession.html
     */
    public function handle()
    {
        $vpnSession = $this->model;
        $availabilityZone = $vpnSession->vpnService->router->availabilityZone;

        if (empty($vpnSession->psk)) {
            $this->fail(new \Exception('Failed to load PSK for VPN Session ' . $vpnSession->id));
            return;
        }

        $router = $vpnSession->vpnService->router;

        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/locale-services/' . $router->id .
            '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
            '/sessions/' . $vpnSession->id,
            [
                'json' => [
                    'resource_type' => 'PolicyBasedIPSecVpnSession',
                    'authentication_mode' => 'PSK',
                    'psk' => $vpnSession->psk,
                    'display_name' => $vpnSession->id,
                    'dpd_profile_path' => '/infra/ipsec-vpn-dpd-profiles/' . $vpnSession->vpnProfileGroup->dpd_profile_id,
                    'ike_profile_path' => '/infra/ipsec-vpn-ike-profiles/' . $vpnSession->vpnProfileGroup->ike_profile_id,
                    'tunnel_profile_path' => '/infra/ipsec-vpn-tunnel-profiles/' . $vpnSession->vpnProfileGroup->ipsec_profile_id,
                    'local_endpoint_path' => '/infra/tier-1s/' . $router->id .
                        '/locale-services/' . $router->id .
                        '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
                        '/local-endpoints/' . $vpnSession->vpnEndpoint->id,
                    'peer_address' => $vpnSession->remote_ip,
                    'peer_id' => $vpnSession->remote_ip,
                    'rules' => [
                        [
                            'resource_type' => 'IPSecVpnRule',
                            'id' => $vpnSession->id . '-custom-rule-1',
                            'sources' => $vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->get()->pluck('ip_address')->map(function ($subnet) {
                                $subnetParsed = Subnet::fromString((string) Str::of($subnet)->trim());
                                return [
                                    'subnet' => $subnetParsed->getStartAddress()->toString() . '/' . $subnetParsed->getNetworkPrefix()
                                ];
                            })->toArray(),
                            'destinations' => $vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->get()->pluck('ip_address')->map(function ($subnet) {
                                $subnetParsed = Subnet::fromString((string) Str::of($subnet)->trim());
                                return [
                                    'subnet' => $subnetParsed->getStartAddress()->toString() . '/' . $subnetParsed->getNetworkPrefix()
                                ];
                            })->toArray()
                        ]
                    ]
                ]
            ]
        );
    }
}

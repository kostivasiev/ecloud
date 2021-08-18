<?php
namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;

    private VpnEndpoint $model;

    public function __construct(VpnEndpoint $vpnEndpoint)
    {
        $this->model = $vpnEndpoint;
    }

    /**
     * See: https://network-man0.ecloud-service.ukfast.co.uk/policy/api_includes/method_CreateOrPatchTier1IPSecVpnLocalEndpoint.html
     */
    public function handle()
    {
        $vpnEndpoint = $this->model;

        if (!$vpnEndpoint->vpnService->router) {
            $this->fail(new \Exception('Failed to load router for VPN Service ' . $vpnEndpoint->vpnService->id));
            return;
        }

        if (!$vpnEndpoint->floatingIp) {
            $this->fail(new \Exception('Failed to load floating IP for VPN Endpoint ' . $vpnEndpoint->id));
            return;
        }

        $vpnEndpoint->vpnService->router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $vpnEndpoint->vpnService->router->id .
            '/locale-services/' . $vpnEndpoint->vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnEndpoint->vpnService->id . '/local-endpoints/' . $vpnEndpoint->id,
            [
                'json' => [
                    'resource_type' => 'IPSecVpnLocalEndpoint',
                    'display_name' => $vpnEndpoint->id,
                    'description' => $vpnEndpoint->name,
                    'local_id' => $vpnEndpoint->floatingIp->ip_address,
                    'local_address' => $vpnEndpoint->floatingIp->ip_address
                ]
            ]
        );
    }
}

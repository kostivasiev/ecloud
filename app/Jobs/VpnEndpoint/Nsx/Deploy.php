<?php

namespace App\Jobs\VpnEndpoint\Nsx;

use App\Jobs\TaskJob;

class Deploy extends TaskJob
{
    public function handle()
    {
        $vpnEndpoint = $this->task->resource;

        if (!$vpnEndpoint->vpnService->router) {
            $this->fail(new \Exception('Failed to load router for VPN Service ' . $vpnEndpoint->vpnService->id));
            return;
        }

        if (!$vpnEndpoint->floatingIpResource()->exists()) {
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
                    'local_id' => $vpnEndpoint->floatingIpResource->floatingIp->ip_address,
                    'local_address' => $vpnEndpoint->floatingIpResource->floatingIp->ip_address
                ]
            ]
        );
    }
}

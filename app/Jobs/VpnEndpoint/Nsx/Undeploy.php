<?php

namespace App\Jobs\VpnEndpoint\Nsx;

use App\Jobs\TaskJob;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $vpnEndpoint = $this->task->resource;
        $router = $vpnEndpoint->vpnService->router;

        $router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/locale-services/' . $router->id .
            '/ipsec-vpn-services/' . $vpnEndpoint->vpnService->id .
            '/local-endpoints/' . $vpnEndpoint->id
        );
    }
}

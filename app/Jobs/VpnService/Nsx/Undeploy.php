<?php

namespace App\Jobs\VpnService\Nsx;

use App\Jobs\TaskJob;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $vpnService = $this->task->resource;
        $vpnService->router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $vpnService->router->id .
            '/locale-services/' . $vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnService->id
        );
    }
}

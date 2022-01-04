<?php

namespace App\Jobs\VpnEndpoint\Nsx;

use App\Jobs\TaskJob;
use App\Traits\V2\Jobs\Nsx\AwaitRealizedState;

class DeployCheck extends TaskJob
{
    use AwaitRealizedState;

    public $tries = 500;
    public $backoff = 5;

    public function handle()
    {
        $vpnEndpoint = $this->task->resource;
        $availabilityZone = $vpnEndpoint->vpnService->router->availabilityZone;
        $intentPath = '/infra/tier-1s/' . $vpnEndpoint->vpnService->router->id .
            '/locale-services/' . $vpnEndpoint->vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnEndpoint->vpnService->id . '/local-endpoints/' . $vpnEndpoint->id;

        $this->awaitRealizedState($vpnEndpoint, $availabilityZone, $intentPath);
    }
}

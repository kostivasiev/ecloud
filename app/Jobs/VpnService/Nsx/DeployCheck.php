<?php

namespace App\Jobs\VpnService\Nsx;

use App\Jobs\TaskJob;
use App\Traits\V2\Jobs\Nsx\AwaitRealizedState;

class DeployCheck extends TaskJob
{
    use AwaitRealizedState;

    public $tries = 500;
    public $backoff = 5;

    public function handle()
    {
        $vpnService = $this->task->resource;
        $availabilityZone = $vpnService->router->availabilityZone;
        $intentPath = '/infra/tier-1s/' . $vpnService->router->id .
            '/locale-services/' . $vpnService->router->id .
            '/ipsec-vpn-services/';

        $this->awaitRealizedState($vpnService, $availabilityZone, $intentPath);
    }
}

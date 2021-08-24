<?php

namespace App\Jobs\Nsx\VpnSession;

use App\Jobs\Job;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private VpnSession $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    /**
     * See: https://185.197.63.88/policy/api_includes/method_DeleteTier1IPSecVpnSession.html
     */
    public function handle()
    {
        $vpnSession = $this->model;

        $vpnSession->vpnService->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $vpnSession->vpnService->router->id .
            '/locale-services/' . $vpnSession->vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
            '/sessions/' . $vpnSession->id
        );
    }
}

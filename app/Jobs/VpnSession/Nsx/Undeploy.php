<?php

namespace App\Jobs\VpnSession\Nsx;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $vpnSession = $this->task->resource;

        try {
            $vpnSession->vpnService->availabilityZone->nsxService()->get(
                '/policy/api/v1/infra/tier-1s/' . $vpnSession->vpnService->router->id .
                '/locale-services/' . $vpnSession->vpnService->router->id .
                '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
                '/sessions/' . $vpnSession->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("VPN session already removed, skipping");
                return;
            }

            throw $e;
        }

        $vpnSession->vpnService->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $vpnSession->vpnService->router->id .
            '/locale-services/' . $vpnSession->vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
            '/sessions/' . $vpnSession->id
        );
    }
}

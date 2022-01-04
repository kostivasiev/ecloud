<?php

namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    public $tries = 360;
    public $backoff = 5;

    public function handle()
    {
        $vpnEndpoint = $this->task->resource;
        $router = $vpnEndpoint->vpnService->router;

        $response = $router->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/locale-services/' . $router->id .
            '/ipsec-vpn-services/' . $vpnEndpoint->vpnService->id .
            '/local-endpoints?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());

        foreach ($response->results as $result) {
            if ($vpnEndpoint->id === $result->id) {
                $this->info(
                    'Waiting for VPN Endpoint ' . $vpnEndpoint->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}

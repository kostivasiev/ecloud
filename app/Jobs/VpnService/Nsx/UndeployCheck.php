<?php

namespace App\Jobs\VpnService\Nsx;

use App\Jobs\TaskJob;

class UndeployCheck extends TaskJob
{
    public $tries = 360;
    public $backoff = 5;

    public function handle()
    {
        $vpnService = $this->task->resource;

        $response = $vpnService->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $vpnService->router->id .
            '/locale-services/' . $vpnService->router->id .
            '/ipsec-vpn-services?include_mark_for_delete_objects=true'
        );

        $response = json_decode($response->getBody()->getContents());

        foreach ($response->results as $result) {
            if ($vpnService->id === $result->id) {
                $this->info(
                    'Waiting for ' . $vpnService->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}

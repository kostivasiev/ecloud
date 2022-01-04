<?php

namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 360;
    public $backoff = 5;

    private VpnEndpoint $model;

    public function __construct(VpnEndpoint $vpnEndpoint)
    {
        $this->model = $vpnEndpoint;
    }

    public function handle()
    {
        $vpnEndpoint = $this->model;
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
                Log::info(
                    'Waiting for VPN Endpoint ' . $vpnEndpoint->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}

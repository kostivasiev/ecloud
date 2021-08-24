<?php

namespace App\Jobs\Nsx\VpnSession;

use App\Jobs\Job;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 360;
    public $backoff = 5;

    private VpnSession $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $vpnSession = $this->model;

        $response = $vpnSession->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $vpnSession->vpnService->router->id .
            '/locale-services/' . $vpnSession->vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnSession->vpnService->id .
            '/sessions/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $vpnSession->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}

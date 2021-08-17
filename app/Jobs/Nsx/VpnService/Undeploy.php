<?php

namespace App\Jobs\Nsx\VpnService;

use App\Jobs\Job;
use App\Models\V2\VpnService;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private VpnService $model;

    public function __construct(VpnService $vpnService)
    {
        $this->model = $vpnService;
    }

    public function handle()
    {
        try {
            $this->model->router->availabilityZone->nsxService()->delete(
                '/policy/api/v1/infra/tier-1s/' . $this->model->router->id .
                '/locale-services/' . $this->model->router->id .
                '/ipsec-vpn-services/' . $this->model->id
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::info("VPN Service not found, skipping");
                return true;
            }
            throw $exception;
        }
    }
}

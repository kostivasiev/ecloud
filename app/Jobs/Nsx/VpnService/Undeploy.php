<?php

namespace App\Jobs\Nsx\VpnService;

use App\Jobs\Job;
use App\Models\V2\VpnService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

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
        $this->model->router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/tier-1s/' . $this->model->router->id .
            '/locale-services/' . $this->model->router->id .
            '/ipsec-vpn-services/' . $this->model->id
        );
    }
}

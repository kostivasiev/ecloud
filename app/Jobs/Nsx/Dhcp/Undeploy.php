<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private Dhcp $model;

    public function __construct(Dhcp $dhcp)
    {
        $this->model = $dhcp;
    }

    public function handle()
    {
        $this->model->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/dhcp-server-configs/' . $this->model->id
        );
    }
}

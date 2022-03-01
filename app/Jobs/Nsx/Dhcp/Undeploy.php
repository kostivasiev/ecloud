<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\TaskJob;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $dhcp = $this->task->resource;

        $dhcp->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id
        );
    }
}

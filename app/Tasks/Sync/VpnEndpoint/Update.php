<?php

namespace App\Tasks\Sync\VpnEndpoint;

use App\Jobs\Nsx\VpnEndpoint\Deploy;
use App\Jobs\VpnEndpoint\CreateFloatingIp;
use App\Jobs\VpnEndpoint\Nsx\DeployCheck;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateFloatingIp::class,
            Deploy::class,
            DeployCheck::class,
        ];
    }
}

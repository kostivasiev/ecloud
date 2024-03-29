<?php

namespace App\Tasks\Sync\VpnEndpoint;

use App\Jobs\VpnEndpoint\AssignFloatingIP;
use App\Jobs\VpnEndpoint\CreateFloatingIp;
use App\Jobs\VpnEndpoint\Nsx\Deploy;
use App\Jobs\VpnEndpoint\Nsx\DeployCheck;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateFloatingIp::class,
            AssignFloatingIp::class,
            Deploy::class,
            DeployCheck::class,
        ];
    }
}

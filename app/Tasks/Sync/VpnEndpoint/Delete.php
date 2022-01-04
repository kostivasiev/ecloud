<?php

namespace App\Tasks\Sync\VpnEndpoint;

use App\Jobs\VpnEndpoint\Nsx\Undeploy;
use App\Jobs\VpnEndpoint\Nsx\UndeployCheck;
use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            Undeploy::class,
            UndeployCheck::class,
            UnassignFloatingIP::class,
        ];
    }
}

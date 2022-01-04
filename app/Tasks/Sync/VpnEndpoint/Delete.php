<?php

namespace App\Tasks\Sync\VpnEndpoint;

use App\Jobs\Nsx\VpnEndpoint\Undeploy;
use App\Jobs\Nsx\VpnEndpoint\UndeployCheck;
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

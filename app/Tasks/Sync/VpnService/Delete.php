<?php

namespace App\Tasks\Sync\VpnService;

use App\Jobs\VpnService\Nsx\Undeploy;
use App\Jobs\VpnService\Nsx\UndeployCheck;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            Undeploy::class,
            UndeployCheck::class,
        ];
    }
}

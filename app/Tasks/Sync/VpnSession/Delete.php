<?php

namespace App\Tasks\Sync\VpnSession;

use App\Jobs\Nsx\VpnSession\UndeployCheck;
use App\Jobs\VpnSession\Nsx\Undeploy;
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

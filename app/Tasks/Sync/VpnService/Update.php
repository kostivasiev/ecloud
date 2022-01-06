<?php

namespace App\Tasks\Sync\VpnService;

use App\Jobs\VpnService\Nsx\Deploy;
use App\Jobs\VpnService\Nsx\DeployCheck;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            Deploy::class,
            DeployCheck::class,
        ];
    }
}

<?php

namespace App\Tasks\Sync\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Undeploy;
use App\Jobs\Nsx\FirewallPolicy\UndeployCheck;
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

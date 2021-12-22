<?php

namespace App\Tasks\Sync\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\DeployCheck;
use App\Jobs\Nsx\FirewallPolicy\UndeployTrashedRules;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            Deploy::class,
            UndeployTrashedRules::class,
            DeployCheck::class,
        ];
    }
}

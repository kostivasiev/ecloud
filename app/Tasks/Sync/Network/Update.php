<?php

namespace App\Tasks\Sync\Network;

use App\Jobs\Network\AwaitRouterSync;
use App\Jobs\Network\CreateSystemRules;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Jobs\Network\DeploySecurityProfile;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            AwaitRouterSync::class,
//            CreateSystemRules::class,
        // create lm network policy rule
            Deploy::class,
            DeploySecurityProfile::class,
            DeployDiscoveryProfile::class,
        ];
    }
}

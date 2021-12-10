<?php

namespace App\Tasks\Sync\Network;

use App\Jobs\Network\AwaitNetworkPolicyRemoval;
use App\Jobs\Network\AwaitPortRemoval;
use App\Jobs\Network\DeleteNetworkPolicy;
use App\Jobs\Network\UndeploySecurityProfiles;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteNetworkPolicy::class,
            AwaitNetworkPolicyRemoval::class,
            AwaitPortRemoval::class,
            UndeploySecurityProfiles::class,
        ];
    }
}

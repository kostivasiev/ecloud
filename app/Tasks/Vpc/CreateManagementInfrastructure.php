<?php

namespace App\Tasks\Vpc;

use App\Jobs\Network\CreateManagementNetwork;
use App\Jobs\Router\CreateManagementFirewallPolicies;
use App\Jobs\Router\CreateManagementNetworkPolicies;
use App\Jobs\Router\CreateManagementRouter;
use App\Tasks\Task;

class CreateManagementInfrastructure extends Task
{
    const TASK_NAME = 'create_management_infrastructure';

    public function jobs()
    {
        return [
            CreateManagementRouter::class,
            CreateManagementNetwork::class,
            CreateManagementFirewallPolicies::class,
            CreateManagementNetworkPolicies::class,
        ];
    }
}

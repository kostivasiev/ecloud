<?php

namespace App\Tasks\Sync\Router;

use App\Jobs\Network\CreateManagementNetwork;
use App\Jobs\Router\AwaitDhcpSync;
use App\Jobs\Router\CreateDhcp;
use App\Jobs\Router\CreateManagementFirewallPolicies;
use App\Jobs\Router\CreateManagementNetworkPolicies;
use App\Jobs\Router\CreateManagementRouter;
use App\Jobs\Router\Deploy;
use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateManagementRouter::class,
            CreateManagementNetwork::class,
            CreateManagementFirewallPolicies::class,
            CreateManagementNetworkPolicies::class,
            Deploy::class,
            DeployRouterLocale::class,
            DeployRouterDefaultRule::class,
            CreateDhcp::class,
            AwaitDhcpSync::class,
        ];
    }
}

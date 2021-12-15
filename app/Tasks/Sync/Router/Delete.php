<?php

namespace App\Tasks\Sync\Router;

use App\Jobs\Router\AwaitFirewallPolicyRemoval;
use App\Jobs\Router\DeleteFirewallPolicies;
use App\Jobs\Router\Undeploy;
use App\Jobs\Router\UndeployCheck;
use App\Jobs\Router\UndeployRouterLocale;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteFirewallPolicies::class,
            AwaitFirewallPolicyRemoval::class,
            UndeployRouterLocale::class,
            Undeploy::class,
            UndeployCheck::class,
        ];
    }
}

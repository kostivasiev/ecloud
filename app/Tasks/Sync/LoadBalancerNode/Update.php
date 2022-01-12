<?php

namespace App\Tasks\Sync\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\CreateInstance;
use App\Jobs\LoadBalancerNode\DeployInstance;
use App\Jobs\LoadBalancerNode\GetWardenCredentials;
use App\Jobs\LoadBalancerNode\RegisterNode;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            GetWardenCredentials::class,
            CreateInstance::class,
            RegisterNode::class,
            DeployInstance::class,
        ];
    }
}

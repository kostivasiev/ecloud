<?php

namespace App\Tasks\Sync\Vip;

use App\Jobs\LoadBalancer\DeployCluster;
use App\Jobs\Vip\UnassignClusterIp;
use App\Jobs\Vip\UnassignFloatingIp;
use App\Jobs\Vip\UnassignFromLoadBalancerCluster;
use App\Jobs\Vip\UnassignFromNics;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            UnassignFromLoadBalancerCluster::class,
            UnassignFloatingIp::class,
            UnassignFromNics::class,
            UnassignClusterIp::class,
            DeployCluster::class,
        ];
    }
}

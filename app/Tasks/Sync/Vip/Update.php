<?php

namespace App\Tasks\Sync\Vip;

use App\Jobs\LoadBalancer\DeployCluster;
use App\Jobs\Vip\AssignFloatingIp;
use App\Jobs\Vip\AssignIpAddress;
use App\Jobs\Vip\AssignToLoadBalancerCluster;
use App\Jobs\Vip\AssignToNics;
use App\Jobs\Vip\CreateFloatingIp;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            AssignIpAddress::class,
            AssignToNics::class,
            CreateFloatingIp::class,
            AssignFloatingIp::class,
            AssignToLoadBalancerCluster::class,
            DeployCluster::class,
        ];
    }
}

<?php

namespace App\Tasks\Sync\Vip;

use App\Jobs\Vip\AssignFloatingIp;
use App\Jobs\Vip\AssignIpAddress;
use App\Jobs\Vip\AssignToLoadBalancerCluster;
use App\Jobs\Vip\AssignToNics;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            AssignIpAddress::class,
            AssignToNics::class,

            // Assign floating ip to the cluster ip of the vip
            AssignFloatingIp::class,

            // Assign the VIP to the cluster via the load balancer API
            AssignToLoadBalancerCluster::Class
        ];
    }
}

<?php

namespace App\Tasks\Sync\Vip;

use App\Jobs\Vip\AssignFloatingIp;
use App\Jobs\Vip\AssignIpAddress;
use App\Jobs\Vip\AssignToLoadBalancerCluster;
use App\Jobs\Vip\AssignToNics;
use App\Jobs\Vip\CreateNics;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            // Assign an IP address to the vip
            AssignIpAddress::class,

            // Create a new nic on the lb's instances (if the supplied network doesn't have a NIC already)
            CreateNics::class,


            // Assign VIP's IP address to each of the load balancer instances NICs
            AssignToNics::class,

            // Assign floating ip to the cluster ip of the vip
            AssignFloatingIp::class,

            // Assign the VIP to the cluster via the load balancer API
            AssignToLoadBalancerCluster::Class
        ];
    }
}

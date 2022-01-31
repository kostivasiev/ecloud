<?php

namespace App\Tasks\Sync\Vip;

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

            // TODO  Re-deploy the load balancer config (LB /loadbalancers/v2/clusters/{id}/deploy)
            //DeployConfig::class, // we may already have this, Tim is doing in https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/1323
        ];
    }
}

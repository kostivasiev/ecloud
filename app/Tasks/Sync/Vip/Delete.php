<?php

namespace App\Tasks\Sync\Vip;

use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            // DELETE /loadbalancers/v2/vips/{vipId}
            UnassignFromLoadBalancerCluster::class,

            UnassignFloatingIp::class,

            UnassignFromNics::class,  // App\Jobs\Tasks\Nic\DisassociateIp

            DeleteClusterIp::class,

            // TODO  Re-deploy the load balancer config (LB /loadbalancers/v2/clusters/{id}/deploy)
            //DeployConfig::class, // we may already have this, TIm is doing in https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/1323
        ];
    }
}

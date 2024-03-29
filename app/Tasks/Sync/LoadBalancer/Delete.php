<?php

namespace App\Tasks\Sync\LoadBalancer;

use App\Jobs\LoadBalancer\DeleteAntiAffinity;
use App\Jobs\LoadBalancer\DeleteCluster;
use App\Jobs\LoadBalancer\DeleteCredentials;
use App\Jobs\LoadBalancer\DeleteLoadBalancerNodes;
use App\Jobs\LoadBalancer\DeleteNetworks;
use App\Jobs\LoadBalancer\DeleteVips;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteVips::class,
            DeleteNetworks::class,
            DeleteAntiAffinity::class,
            DeleteLoadBalancerNodes::class,
            DeleteCluster::class,
            DeleteCredentials::class,
        ];
    }
}

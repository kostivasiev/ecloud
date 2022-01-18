<?php

namespace App\Tasks\Sync\LoadBalancer;

use App\Jobs\LoadBalancer\DeleteCluster;
use App\Jobs\LoadBalancer\DeleteCredentials;
use App\Jobs\LoadBalancer\DeleteNetworks;
use App\Jobs\LoadBalancer\DeleteNodes;
use App\Jobs\LoadBalancer\DeleteVips;
use App\Jobs\LoadBalancer\PrepareNodes;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteVips::class,
            DeleteNetworks::class,
            PrepareNodes::class,
            DeleteCluster::class,
            DeleteNodes::class,
            DeleteCredentials::class,
        ];
    }
}

<?php

namespace App\Tasks\Sync\LoadBalancer;

use App\Jobs\LoadBalancer\DeleteCluster;
use App\Jobs\LoadBalancer\DeleteCredentials;
use App\Jobs\LoadBalancer\DeleteInstances;
use App\Jobs\LoadBalancer\DeleteNodes;
use App\Jobs\LoadBalancer\DeleteVips;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteVips::class,
            DeleteInstances::class,
            DeleteNodes::class,
            DeleteCluster::class,
            DeleteCredentials::class,
        ];
    }
}

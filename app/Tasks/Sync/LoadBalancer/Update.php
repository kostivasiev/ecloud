<?php

namespace App\Tasks\Sync\LoadBalancer;

use App\Jobs\LoadBalancer\CreateCluster;
use App\Jobs\LoadBalancer\CreateCredentials;
use App\Jobs\LoadBalancer\CreateInstances;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateCluster::class,
            CreateCredentials::class,
            CreateInstances::class,
        ];
    }
}

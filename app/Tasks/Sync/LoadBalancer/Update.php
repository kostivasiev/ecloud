<?php

namespace App\Tasks\Sync\LoadBalancer;

use App\Jobs\LoadBalancer\AddNetworks;
use App\Jobs\LoadBalancer\ConfigurePeers;
use App\Jobs\LoadBalancer\CreateCluster;
use App\Jobs\LoadBalancer\CreateCredentials;
use App\Jobs\LoadBalancer\CreateNodes;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateCluster::class,
            CreateCredentials::class,
            CreateNodes::class,
            AddNetworks::class,
            ConfigurePeers::class,
        ];
    }
}

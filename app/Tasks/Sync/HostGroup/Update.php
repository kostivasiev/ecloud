<?php

namespace App\Tasks\Sync\HostGroup;

use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNodeProfile;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateCluster::class,
            CreateTransportNodeProfile::class,
            PrepareCluster::class,
        ];
    }
}

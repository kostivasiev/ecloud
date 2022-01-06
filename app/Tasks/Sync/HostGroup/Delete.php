<?php

namespace App\Tasks\Sync\HostGroup;

use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteTransportNodeProfile::class,
            DeleteCluster::class,
        ];
    }
}

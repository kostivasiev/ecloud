<?php

namespace App\Tasks\Sync\Dhcp;

use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            Undeploy::class,
            UndeployCheck::class,
        ];
    }
}

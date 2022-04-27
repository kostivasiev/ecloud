<?php

namespace App\Tasks\Sync\Nic;

use App\Jobs\Nic\Deploy;
use App\Jobs\Nsx\Nic\CreateDHCPLease;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            Deploy::class,
            CreateDHCPLease::class,
        ];
    }
}

<?php

namespace App\Tasks\Sync\Nic;

use App\Jobs\Nic\CheckIpAssignment;
use App\Jobs\Nic\Undeploy;
use App\Jobs\Nsx\Nic\RemoveDHCPLease;
use App\Jobs\Nsx\Nic\RemoveIpAddressBindings;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            CheckIpAssignment::class,
            RemoveDHCPLease::class,
            RemoveIpAddressBindings::class,
            Undeploy::class
        ];
    }
}

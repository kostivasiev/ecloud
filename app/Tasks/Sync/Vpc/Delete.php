<?php

namespace App\Tasks\Sync\Vpc;

use App\Jobs\Network\DeleteManagementNetworks;
use App\Jobs\Router\DeleteManagementRouters;
use App\Jobs\Vpc\AwaitDhcpRemoval;
use App\Jobs\Vpc\DeleteDhcps;
use App\Jobs\Vpc\RemoveLanPolicies;
use App\Jobs\Vpc\RemoveVPCFolder;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteManagementNetworks::class,
            DeleteManagementRouters::class,
            RemoveLanPolicies::class,
            DeleteDhcps::class,
            AwaitDhcpRemoval::class,
            RemoveVPCFolder::class,
        ];
    }
}

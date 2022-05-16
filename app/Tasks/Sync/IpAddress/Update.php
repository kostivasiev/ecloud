<?php

namespace App\Tasks\Sync\IpAddress;

use App\Jobs\IpAddress\AllocateIpToIpAddress;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            AllocateIpToIpAddress::class
        ];
    }
}

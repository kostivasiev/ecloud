<?php

namespace App\Tasks\Nic;

use App\Jobs\Nsx\Nic\BindIpAddress;
use App\Tasks\Task;

class AssociateIp extends Task
{
    public static string $name = 'associate_ip';

    public function jobs()
    {
        return [
            BindIpAddress::class
        ];
    }
}

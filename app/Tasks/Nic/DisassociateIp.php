<?php

namespace App\Tasks\Nic;

use App\Tasks\Task;
use \App\Jobs\Nsx\Nic\UnbindIpAddress;

class DisassociateIp extends Task
{
    public static string $name = 'disassociate_ip';

    public function jobs()
    {
        return [
            UnbindIpAddress::class
        ];
    }
}

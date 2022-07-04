<?php

namespace App\Tasks\Nic;

use App\Tasks\Task;

class DisassociateIp extends Task
{
    public static string $name = 'disassociate_ip';

    public function jobs()
    {
        return [
            \App\Jobs\Nic\DisassociateIp::class,
        ];
    }
}

<?php

namespace App\Tasks\Nic;

use App\Tasks\Task;

class DisassociateIp extends Task
{
    const TASK_NAME = 'disassociate_ip';

    public function jobs()
    {
        return [
            \App\Jobs\Nic\DisassociateIp::class,
        ];
    }
}

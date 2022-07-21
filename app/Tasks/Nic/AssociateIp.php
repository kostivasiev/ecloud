<?php

namespace App\Tasks\Nic;

use App\Tasks\Task;

class AssociateIp extends Task
{
    const TASK_NAME = 'associate_ip';

    public function jobs()
    {
        return [
            \App\Jobs\Nic\AssociateIp::class,
        ];
    }
}

<?php

namespace App\Tasks\Nic;

use App\Tasks\Task;

class AssociateIp extends Task
{
    public static string $name = 'associate_ip';

    public function jobs()
    {
        return [
            \App\Jobs\Nic\AssociateIp::class,
        ];
    }
}

<?php

namespace App\Tasks\Sync\LoadBalancerNetwork;

use App\Jobs\LoadBalancerNetwork\AwaitNicDeletion;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            AwaitNicDeletion::class
        ];
    }
}

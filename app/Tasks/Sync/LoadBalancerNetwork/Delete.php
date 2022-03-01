<?php

namespace App\Tasks\Sync\LoadBalancerNetwork;

use App\Jobs\LoadBalancerNetwork\DeleteNics;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            DeleteNics::class
        ];
    }
}

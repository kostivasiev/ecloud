<?php

namespace App\Tasks\Sync\LoadBalancerNetwork;

use App\Jobs\LoadBalancerNetwork\CreateNics;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreateNics::class
        ];
    }
}

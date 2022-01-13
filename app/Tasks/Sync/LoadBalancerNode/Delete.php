<?php

namespace App\Tasks\Sync\LoadBalancerNode;

use App\Jobs\LoadBalancerNode\UnregisterNode;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            UnregisterNode::class,
        ];
    }
}

<?php

namespace App\Jobs\Sync\LoadBalancerCluster;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
//                new AwaitRouterSync($this->task->resource),
//                new Deploy($this->task->resource),
//                new DeploySecurityProfile($this->task->resource),
//                new DeployDiscoveryProfile($this->task->resource),
            ],
        ])->dispatch();
    }
}

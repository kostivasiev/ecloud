<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Job;
use App\Traits\V2\JobModel;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, JobModel;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        // Here we chain AllocateIp and AllocateIpCheck
        $this->updateTaskBatch([
            [
                new AllocateIp($this->task->resource),
                new AwaitNatSync($this->task->resource),
            ]
        ])->dispatch();
    }
}

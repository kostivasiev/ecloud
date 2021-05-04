<?php

namespace App\Jobs\Sync\Nic;

use App\Jobs\Job;
use App\Jobs\Nsx\Nic\CreateDHCPLease;
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
        $this->updateTaskBatch([
            [
                new CreateDHCPLease($this->task->resource),
            ]
        ])->dispatch();
    }
}

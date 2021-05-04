<?php

namespace App\Jobs\Sync\Dhcp;

use App\Jobs\Job;
use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Task;
use App\Traits\V2\JobModel;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, JobModel;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ]
        ])->dispatch();
    }
}

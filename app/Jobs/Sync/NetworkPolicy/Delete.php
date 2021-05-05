<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableModelJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new DeleteChildResources($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\Undeploy($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\UndeployCheck($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Undeploy($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\UndeployCheck($this->task->resource),
            ]
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableModelJob;

    /** @var Task */
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
            ]
        ])->dispatch();
    }
}

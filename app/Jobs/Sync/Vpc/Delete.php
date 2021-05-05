<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Job;
use App\Jobs\Vpc\AwaitDhcpRemoval;
use App\Jobs\Vpc\DeleteDhcps;
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
                new DeleteDhcps($this->task->resource),
                new AwaitDhcpRemoval($this->task->resource),
            ]
        ])->dispatch();
    }
}

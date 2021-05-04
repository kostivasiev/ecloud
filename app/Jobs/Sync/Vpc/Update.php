<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Job;
use App\Jobs\Vpc\AwaitDhcpSync;
use App\Jobs\Vpc\CreateDhcps;
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
                new CreateDhcps($this->task->resource),
                new AwaitDhcpSync($this->task->resource),
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\Dhcp;

use App\Jobs\Job;
use App\Jobs\Nsx\Dhcp\Create;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableModelJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new Create($this->task->resource),
            ]
        ])->dispatch();
    }
}

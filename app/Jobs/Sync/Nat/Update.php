<?php

namespace App\Jobs\Sync\Nat;

use App\Jobs\Job;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Jobs\Nat\Deploy;
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
                new AwaitIPAddressAllocation($this->task->resource),
                new Deploy($this->task->resource),
            ]
        ])->dispatch();
    }
}

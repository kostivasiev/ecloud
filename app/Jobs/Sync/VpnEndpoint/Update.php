<?php

namespace App\Jobs\Sync\VpnEndpoint;

use App\Jobs\Job;
use App\Jobs\Nsx\VpnEndpoint\CreateEndpoint;
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
                new CreateEndpoint($this->task->resource),
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\Job;
use App\Jobs\FloatingIp\ResetRdnsHostname;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new ResetRdnsHostname($this->task),
            ],
        ])->dispatch();
    }
}

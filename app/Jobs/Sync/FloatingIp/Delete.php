<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\Job;
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
        $floatingIp = $this->task->resource;

        $this->task->completed = true;
        $this->task->save();

        $floatingIp->deleted = time();
        $floatingIp->save();
        $floatingIp->delete();
    }
}

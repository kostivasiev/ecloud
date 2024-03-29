<?php

namespace App\Jobs\Tasks\FloatingIp;

use App\Jobs\FloatingIp\DeleteFloatingIpResource;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class Unassign extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

    const TASK_NAME = 'floating_ip_unassign';

    private Task $task;

    private FloatingIp $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new DeleteFloatingIpResource($this->task),
            ]
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Tasks\VpnEndpoint;

use App\Jobs\FloatingIp\CreateFloatingIp;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Support\Resource;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class CreateAssignFloatingIp extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $task = $this->task;

        $this->updateTaskBatch([
            [
                new CreateFloatingIp($this->task),
            ]
        ], function () use ($task) {
            $floatingIp = FloatingIp::findOrFail($task->data['resource_id']);
            $floatingIp->resource()->associate($task->resource);
            $floatingIp->save();
        })->dispatch();
    }
}

<?php

namespace App\Jobs\Tasks\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\RemoveNats;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class Unassign extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

    public static string $name = 'floating_ip_unassign';

    private Task $task;

    private FloatingIp $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $task = $this->task;

        $this->updateTaskBatch([
            [
                new RemoveNats($this->task->resource),
                new AwaitNatRemoval($this->task->resource),
            ]
        ], function () use ($task) {
            $task->resource->resource()->dissociate();
            $task->resource->save();
        })->dispatch();
    }
}

<?php

namespace App\Jobs\Tasks\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\FloatingIp\CreateNats;
use App\Jobs\Job;
use App\Models\V2\Task;
use App\Support\Resource;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class Assign extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

    static string $name = 'floating_ip_assign';

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $task = $this->task;
        $resource = Resource::classFromId($task->data['resource_id'])::findOrFail($task->data['resource_id']);

        $this->updateTaskBatch([
            [
                new CreateNats($this->task->resource, $resource),
                new AwaitNatSync($this->task->resource, $resource),
            ]
        ], function () use ($task, $resource) {
            $task->resource->resource()->associate($resource);
            $task->resource->save();
        })->dispatch();
    }
}

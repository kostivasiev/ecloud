<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Instance\EndPublicBilling;
use App\Jobs\Job;
use App\Jobs\Kingpin\Instance\MoveToPrivateHostGroup;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class MigratePrivate extends Job
{
    use Batchable, TaskableBatch, LoggableModelJob;

    public Task $task;
    private Instance $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $task = $this->task;

        $newHostGroup = HostGroup::findOrFail($task->data['host_group_id']);

        $this->updateTaskBatch([
            [
                new MoveToPrivateHostGroup($this->model, $newHostGroup->id),
            ]
        ], function () use ($task, $newHostGroup) {
            $task->resource->hostGroup()->associate($newHostGroup);
            $task->resource->saveQuietly();
        })->dispatch();
    }
}

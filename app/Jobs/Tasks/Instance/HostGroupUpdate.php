<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Instance\MoveToHostGroup;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class HostGroupUpdate extends Job
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
        $originalHostGroup = HostGroup::findOrFail($this->model->host_group_id);
        $newHostGroup = HostGroup::findOrFail($task->data['host_group_id']);

        $jobs = [
            new MoveToHostGroup($this->model, $newHostGroup->id),
        ];

        // If hostSpec changes too, then we need to cyclePower on the instance
        if ($originalHostGroup->hostSpec->id != $newHostGroup->hostSpec->id) {
            array_unshift($jobs, new PowerOff($this->model));
            array_push($jobs, new PowerOn($this->model));
        }

        $this->updateTaskBatch($jobs, function () use ($task) {
            $task->resource->host_group_id = $task->data['host_group_id'];
            $task->resource->saveQuietly();
        })->dispatch();
    }
}

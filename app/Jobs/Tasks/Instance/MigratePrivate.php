<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\PowerOn;
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

        $jobs = [
            new MoveToPrivateHostGroup($this->model, $newHostGroup->id),
        ];

        // If we go Public -> Private, or Private -> Private & hostSpec changes we need to power cycle the instance
        if (!$this->model->hostGroup || $this->model->hostGroup->hostSpec->id != $newHostGroup->hostSpec->id) {
            array_unshift($jobs, new PowerOff($this->model));
            array_push($jobs, new PowerOn($this->model));
        }

        $this->updateTaskBatch([$jobs], function () use ($task, $newHostGroup) {
            $task->resource->hostGroup()->associate($newHostGroup);
            $task->resource->saveQuietly();
        })->dispatch();
    }
}

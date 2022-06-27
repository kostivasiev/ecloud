<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Job;
use App\Jobs\Kingpin\Instance\MoveToPublicHostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class MigratePublic extends Job
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

        $this->updateTaskBatch([
            [
                new MoveToPublicHostGroup($this->model),
            ]
        ], function () use ($task) {
            $task->resource->hostGroup()->dissociate();
            $task->resource->saveQuietly();
        })->dispatch();
    }
}

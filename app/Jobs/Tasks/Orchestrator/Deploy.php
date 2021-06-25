<?php

namespace App\Jobs\Tasks\Orchestrator;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class Deploy extends Job
{
    use Batchable, TaskableBatch, LoggableModelJob;

    public Task $task;
    private OrchestratorBuild $model;

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
                // TODO
            ]
        ])->dispatch();
    }
}

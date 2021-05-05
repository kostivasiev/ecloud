<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Router\Deploy;
use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableModelJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new Deploy($this->task->resource),
                new DeployRouterLocale($this->task->resource),
                new DeployRouterDefaultRule($this->task->resource),
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Network\CreateManagementNetwork;
use App\Jobs\Router\AwaitDhcpSync;
use App\Jobs\Router\CreateManagementPolicies;
use App\Jobs\Router\CreateManagementRouter;
use App\Jobs\Router\CreateDhcp;
use App\Jobs\Router\Deploy;
use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new CreateManagementRouter($this->task),
                new CreateManagementNetwork($this->task),
                new CreateManagementPolicies($this->task),
                new Deploy($this->task->resource),
                new DeployRouterLocale($this->task->resource),
                new DeployRouterDefaultRule($this->task->resource),
                new CreateDhcp($this->task->resource),
                new AwaitDhcpSync($this->task->resource),
            ],
        ])->dispatch();
    }
}

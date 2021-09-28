<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Router\AwaitFirewallPolicyRemoval;
use App\Jobs\Router\DeleteDhcp;
use App\Jobs\Router\DeleteFirewallPolicies;
use App\Jobs\Router\Undeploy;
use App\Jobs\Router\UndeployCheck;
use App\Jobs\Router\UndeployRouterLocale;
use App\Jobs\Tasks\AwaitTasks;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new DeleteDhcp($this->task->resource),
                new AwaitTasks($this->task, DeleteDhcp::TASK_WAIT_DATA_KEY),
                new DeleteFirewallPolicies($this->task->resource),
                new AwaitFirewallPolicyRemoval($this->task->resource),
                new UndeployRouterLocale($this->task->resource),
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ]
        ])->dispatch();
    }
}

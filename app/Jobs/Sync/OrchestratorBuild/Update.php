<?php

namespace App\Jobs\Sync\OrchestratorBuild;

use App\Jobs\Job;
use App\Jobs\OrchestratorBuild\AwaitDefaultFirewallPolicies;
use App\Jobs\OrchestratorBuild\AwaitRouters;
use App\Jobs\OrchestratorBuild\AwaitVpcs;
use App\Jobs\OrchestratorBuild\ConfigureDefaultFirewallPolicies;
use App\Jobs\OrchestratorBuild\CreateRouters;
use App\Jobs\OrchestratorBuild\CreateVpcs;
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
                new CreateVpcs($this->task->resource),
                new AwaitVpcs($this->task->resource),
                new CreateRouters($this->task->resource),
                new AwaitRouters($this->task->resource),
                new ConfigureDefaultFirewallPolicies($this->task->resource),
                new AwaitDefaultFirewallPolicies($this->task->resource),
            ]
        ])->dispatch();
    }
}

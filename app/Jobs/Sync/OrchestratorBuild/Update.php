<?php

namespace App\Jobs\Sync\OrchestratorBuild;

use App\Jobs\Job;
use App\Jobs\OrchestratorBuild\AwaitDefaultFirewallPolicies;
use App\Jobs\OrchestratorBuild\AwaitHostGroups;
use App\Jobs\OrchestratorBuild\AwaitHosts;
use App\Jobs\OrchestratorBuild\AwaitInstances;
use App\Jobs\OrchestratorBuild\AwaitLoadBalancers;
use App\Jobs\OrchestratorBuild\AwaitNetworks;
use App\Jobs\OrchestratorBuild\AwaitRouters;
use App\Jobs\OrchestratorBuild\AwaitVpcs;
use App\Jobs\OrchestratorBuild\ConfigureDefaultFirewallPolicies;
use App\Jobs\OrchestratorBuild\CreateHostGroups;
use App\Jobs\OrchestratorBuild\CreateHosts;
use App\Jobs\OrchestratorBuild\CreateInstances;
use App\Jobs\OrchestratorBuild\CreateLoadBalancers;
use App\Jobs\OrchestratorBuild\CreateNetworks;
use App\Jobs\OrchestratorBuild\CreateRouters;
use App\Jobs\OrchestratorBuild\CreateVpcs;
use App\Jobs\OrchestratorBuild\LockConfiguration;
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
                new LockConfiguration($this->task->resource),
                new CreateVpcs($this->task->resource),
                new AwaitVpcs($this->task->resource),
                new CreateRouters($this->task->resource),
                new AwaitRouters($this->task->resource),
                new ConfigureDefaultFirewallPolicies($this->task->resource),
                new AwaitDefaultFirewallPolicies($this->task->resource),
                new CreateNetworks($this->task->resource),
                new AwaitNetworks($this->task->resource),
                new CreateHostGroups($this->task->resource),
                new AwaitHostGroups($this->task->resource),
                new CreateHosts($this->task->resource),
                new AwaitHosts($this->task->resource),
                new CreateInstances($this->task->resource),
                new AwaitInstances($this->task->resource),
                new CreateLoadBalancers($this->task->resource),
                new AwaitLoadBalancers($this->task->resource),
            ]
        ])->dispatch();
    }
}

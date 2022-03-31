<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\AllowLogicMonitor;
use App\Jobs\NetworkPolicy\CreateDefaultNetworkRules;
use App\Jobs\Nsx\DeployCheck;
use App\Jobs\Nsx\NetworkPolicy\UndeployTrashedRules;
use App\Jobs\Nsx\NetworkPolicy\Deploy as DeployNetworkPolicy;
use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy as DeploySecurityGroup;
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
                new DeploySecurityGroup($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->network->router->availabilityZone,
                    '/infra/domains/default/groups/'
                ),
                new CreateDefaultNetworkRules($this->task->resource, $this->task->data),
                new DeployNetworkPolicy($this->task->resource),
                new AllowLogicMonitor($this->task->resource),
                new UndeployTrashedRules($this->task->resource),
                new DeployCheck(
                    $this->task->resource,
                    $this->task->resource->network->router->availabilityZone,
                    '/infra/domains/default/security-policies/'
                )
            ]
        ])->dispatch();
    }
}

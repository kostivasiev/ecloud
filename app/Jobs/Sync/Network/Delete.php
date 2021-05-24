<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitPortRemoval;
use App\Jobs\Network\DeleteNetworkPolicy;
use App\Jobs\Network\AwaitNetworkPolicyRemoval;
use App\Jobs\Network\Undeploy;
use App\Jobs\Network\UndeployCheck;
use App\Jobs\Network\UndeployDiscoveryProfiles;
use App\Jobs\Network\UndeployQoSProfiles;
use App\Jobs\Network\UndeploySecurityProfiles;
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
                new DeleteNetworkPolicy($this->task->resource),
                new AwaitNetworkPolicyRemoval($this->task->resource),
                new AwaitPortRemoval($this->task->resource),
                new UndeploySecurityProfiles($this->task->resource),
                new UndeployDiscoveryProfiles($this->task->resource),
                new UndeployQoSProfiles($this->task->resource),
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ],
        ])->dispatch();
    }
}

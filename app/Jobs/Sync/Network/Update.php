<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitRouterSync;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Jobs\Network\DeploySecurityProfile;
use App\Traits\V2\JobModel;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, JobModel;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new AwaitRouterSync($this->task->resource),
                new Deploy($this->task->resource),
                new DeploySecurityProfile($this->task->resource),
                new DeployDiscoveryProfile($this->task->resource),
            ],
        ])->dispatch();
    }
}

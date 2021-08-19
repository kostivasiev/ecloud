<?php

namespace App\Jobs\Sync\VpnEndpoint;

use App\Jobs\Job;
use App\Jobs\Nsx\VpnEndpoint\Undeploy;
use App\Jobs\Nsx\VpnEndpoint\UndeployCheck;
use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
                new UnassignFloatingIP($this->task->resource),
            ]
        ])->dispatch();
    }
}

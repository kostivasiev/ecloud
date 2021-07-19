<?php

namespace App\Jobs\Sync\Nic;

use App\Jobs\Job;
use App\Jobs\Nic\AwaitFloatingIpUnassign;
use App\Jobs\Nic\UnassignFloatingIP;
use App\Jobs\Nsx\Nic\RemoveDHCPLease;
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
                new RemoveDHCPLease($this->task->resource),
                new UnassignFloatingIP($this->task->resource),
                new AwaitFloatingIpUnassign($this->task->resource)
            ]
        ])->dispatch();
    }
}

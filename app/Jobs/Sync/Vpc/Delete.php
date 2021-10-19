<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Job;
use App\Jobs\Network\DeleteManagementNetwork;
use App\Jobs\Router\DeleteManagementRouter;
use App\Jobs\Vpc\AwaitDhcpRemoval;
use App\Jobs\Vpc\DeleteDhcps;
use App\Jobs\Vpc\RemoveLanPolicies;
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
                new DeleteManagementNetwork($this->task),
                new DeleteManagementRouter($this->task),
                new RemoveLanPolicies($this->task->resource),
                new DeleteDhcps($this->task->resource),
                new AwaitDhcpRemoval($this->task->resource),
            ]
        ])->dispatch();
    }
}

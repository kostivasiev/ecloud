<?php

namespace App\Jobs\Sync\FirewallPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\FirewallPolicy\Undeploy;
use App\Jobs\Nsx\FirewallPolicy\UndeployCheck;
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
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ]
        ])->dispatch();
    }
}

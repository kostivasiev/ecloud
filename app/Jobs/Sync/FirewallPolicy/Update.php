<?php

namespace App\Jobs\Sync\FirewallPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\DeployCheck;
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
                new Deploy($this->task->resource, $this->task->data),
                new DeployCheck($this->task->resource),
            ]
        ])->dispatch();
    }
}

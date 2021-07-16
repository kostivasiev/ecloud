<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Jobs\FloatingIp\AwaitUnassignedNicNatRemoval;
use App\Jobs\FloatingIp\CreateNats;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\FloatingIp\RemoveUnassignedNicNats;
use App\Jobs\Job;
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
                new AllocateIp($this->task->resource),
                new RemoveUnassignedNicNats($this->task->resource),
                new AwaitUnassignedNicNatRemoval($this->task->resource),
                new CreateNats($this->task->resource),
                new AwaitNatSync($this->task->resource),
            ]
        ])->dispatch();
    }
}

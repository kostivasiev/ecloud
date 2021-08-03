<?php

namespace App\Jobs\Sync\VpnEndpoint;

use App\Jobs\FloatingIp\CreateFloatingIp;
use App\Jobs\Job;
use App\Jobs\Nsx\VpnEndpoint\CreateEndpoint;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new CreateFloatingIp($this->task),
                new CreateEndpoint($this->task),
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Tasks\VpnEndpoint;

use App\Jobs\Job;
use App\Jobs\VpnEndpoint\AssignFloatingIP;
use App\Jobs\VpnEndpoint\CreateFloatingIp;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class CreateAssignFloatingIp extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

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
                new AssignFloatingIP($this->task)
            ]
        ])->dispatch();
    }
}

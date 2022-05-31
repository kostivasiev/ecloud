<?php

namespace App\Jobs\Tasks\FloatingIp;

use App\Jobs\FloatingIp\CreateFloatingIpResource;
use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class Assign extends Job
{
    use Batchable, TaskableBatch, LoggableTaskJob;

    public static string $name = 'floating_ip_assign';

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new CreateFloatingIpResource($this->task),
            ]
        ])->dispatch();
    }
}

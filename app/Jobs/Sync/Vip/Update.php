<?php

namespace App\Jobs\Sync\Vip;

use App\Jobs\Job;
use App\Jobs\Vip\AssignFloatingIp;
use App\Jobs\Vip\AssignIpAddress;
use App\Jobs\Vip\AssignToNics;
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
                new AssignIpAddress($this->task->resource),
                new AssignToNics($this->task),

                new AssignFloatingIp($this->task),

            ],
        ])->dispatch();
    }
}

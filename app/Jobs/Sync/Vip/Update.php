<?php

namespace App\Jobs\Sync\Vip;

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
                // assign ip address to the vip

                /// assign ip address to each of the loadbalancer NICs


                new AssignIpAddress($this->task),
                new AssignIpToNics
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\VpnService;

use App\Jobs\Job;
use App\Jobs\Nsx\VpnService\CreateService;
use App\Jobs\Nsx\VpnService\RetrieveServiceUuid;
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
                new CreateService($this->task->resource),
                new RetrieveServiceUuid($this->task->resource),
            ],
        ])->dispatch();
    }
}

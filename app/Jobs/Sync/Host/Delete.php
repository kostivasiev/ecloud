<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
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
        $host = $this->task->resource;
        $this->deleteTaskBatch([
            [
                new \App\Jobs\Kingpin\Host\MaintenanceMode($host),
                new \App\Jobs\Kingpin\Host\DeleteInVmware($host),
                new \App\Jobs\Conjurer\Host\PowerOff($host),
                new \App\Jobs\Artisan\Host\RemoveFrom3Par($host),
                new \App\Jobs\Conjurer\Host\DeleteServiceProfile($host),
            ]
        ])->dispatch();
    }
}

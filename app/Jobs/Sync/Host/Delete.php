<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\RemoveFrom3Par;
use App\Jobs\Conjurer\Host\DeleteServiceProfile;
use App\Jobs\Conjurer\Host\PowerOff;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\DeleteInVmware;
use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Jobs\Nsx\Host\RemoveFromNsGroups;
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
                new MaintenanceMode($host),
                new RemoveFromNsGroups($host),
                new DeleteInVmware($host),
                new PowerOff($host),
                new RemoveFrom3Par($host),
                new DeleteServiceProfile($host),
            ]
        ])->dispatch();
    }
}

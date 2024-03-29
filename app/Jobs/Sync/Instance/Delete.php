<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Instance\Undeploy\AwaitVolumeRemoval;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\DetachSharedVolumes;
use App\Jobs\Instance\Undeploy\RemoveCredentials;
use App\Jobs\Instance\Undeploy\RemoveMonitoring;
use App\Jobs\Instance\Undeploy\RevokeLicenses;
use App\Jobs\Instance\Undeploy\UnassignFloatingIP;
use App\Jobs\Instance\Undeploy\Undeploy;
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
        $this->deleteTaskBatch([
            [
                new RemoveMonitoring($this->task->resource),
                new PowerOff($this->task->resource, true),
                new DetachSharedVolumes($this->task),
                new Undeploy($this->task->resource),
                new DeleteVolumes($this->task->resource),
                new UnassignFloatingIP($this->task),
                new DeleteNics($this->task->resource),
                new AwaitVolumeRemoval($this->task->resource),
                new AwaitNicRemoval($this->task->resource),
                new RemoveCredentials($this->task->resource),
                new RevokeLicenses($this->task->resource),
            ],
        ])->dispatch();
    }
}

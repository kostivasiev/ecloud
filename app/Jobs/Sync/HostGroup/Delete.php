<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableModelJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $hostGroup = $this->task->resource;
        $this->deleteTaskBatch([
                new DeleteTransportNodeProfile($hostGroup),
                new DeleteCluster($hostGroup),
        ])->dispatch();
    }
}

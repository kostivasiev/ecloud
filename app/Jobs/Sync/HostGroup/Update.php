<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNodeProfile;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
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
        $hostGroup = $this->task->resource;
        $this->updateTaskBatch([
            [
                new CreateCluster($hostGroup),
                new CreateTransportNodeProfile($hostGroup),
                new PrepareCluster($hostGroup)
            ],
        ])->dispatch();
    }
}

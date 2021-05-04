<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNode;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
use App\Traits\V2\JobModel;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, JobModel;

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
                new CreateTransportNode($hostGroup),
                new PrepareCluster($hostGroup)
            ],
        ])->dispatch();
    }
}

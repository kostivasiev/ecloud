<?php

namespace App\Jobs\Sync\LoadBalancer;

use App\Jobs\Job;
use App\Jobs\LoadBalancer\CreateCluster;
use App\Jobs\LoadBalancer\CreateCredentials;
use App\Jobs\LoadBalancer\CreateNodes;
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
                new CreateCluster($this->task),
                new CreateCredentials($this->task),
                new CreateNodes($this->task),
                // Todo new AddNetworks($this->task)
            ],
        ])->dispatch();
    }
}

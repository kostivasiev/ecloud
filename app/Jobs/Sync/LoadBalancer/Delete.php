<?php

namespace App\Jobs\Sync\LoadBalancer;

use App\Jobs\Job;
use App\Jobs\LoadBalancer\DeleteCluster;
use App\Jobs\LoadBalancer\DeleteCredentials;
use App\Jobs\LoadBalancer\DeleteInstances;
use App\Jobs\LoadBalancer\DeleteVips;
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
                new DeleteVips($this->task),
//              TODO:  new DeleteNetworks($this->task)
                new DeleteInstances($this->task),
                new DeleteCluster($this->task),
                new DeleteCredentials($this->task),

            ],
        ])->dispatch();
    }
}

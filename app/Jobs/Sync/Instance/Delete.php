<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\Undeploy;
use App\Jobs\Job;
use App\Traits\V2\JobModel;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, JobModel;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new PowerOff($this->task->resource),
                new Undeploy($this->task->resource),
                new DeleteVolumes($this->task->resource),
                new DeleteNics($this->task->resource),
                new AwaitNicRemoval($this->task->resource),
            ],
        ])->dispatch();
    }
}

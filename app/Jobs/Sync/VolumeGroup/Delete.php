<?php

namespace App\Jobs\Sync\VolumeGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    /** @var Task */
    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
//        $this->deleteTaskBatch([
//            [
//            ]
//        ])->dispatch();
        $this->task->resource->delete();
        $this->task->completed = true;
        $this->task->save();
    }
}

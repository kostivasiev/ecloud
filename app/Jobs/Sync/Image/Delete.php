<?php

namespace App\Jobs\Sync\Image;

use App\Jobs\Image\SyncAvailabilityZones;
use App\Jobs\Image\SyncSoftware;
use App\Jobs\Job;
use App\Jobs\Kingpin\Image\DeleteImage;
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
        $image = $this->task->resource;
        $this->deleteTaskBatch([
            [
                new DeleteImage($image),
                new SyncAvailabilityZones($image),
                new SyncSoftware($image),
            ]
        ])->dispatch();
    }
}

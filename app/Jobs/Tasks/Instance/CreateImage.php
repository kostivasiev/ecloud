<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;


class CreateImage extends Job
{
    use TaskableBatch, Batchable, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $task = $this->task;
        $instance = $task->resource;
        $image = Image::findOrFail($task->data['image_id']);

        $this->updateTaskBatch([
            [
                new \App\Jobs\Kingpin\Instance\CreateImage($instance, $image)
            ]
        ])->dispatch();
    }
}

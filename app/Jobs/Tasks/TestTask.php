<?php

namespace App\Jobs\Tasks;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class TestTask extends Job
{
    use TaskableBatch, Batchable;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new TestJobWait()
            ]
        ])->dispatch();
    }
}

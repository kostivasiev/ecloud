<?php

namespace App\Jobs\Sync\InstanceSoftware;

use App\Jobs\Job;
use App\Jobs\InstanceSoftware\RunScripts;
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
                new RunScripts($this->task),
            ],
        ])->dispatch();
    }
}

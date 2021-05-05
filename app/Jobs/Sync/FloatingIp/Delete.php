<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\DeleteNats;
use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batch;

class Delete extends Job
{
    use TaskableBatch, LoggableModelJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $floatingIp = $this->task->resource;
        $this->deleteTaskBatch(
            [
                new DeleteNats($floatingIp),
                new AwaitNatRemoval($floatingIp),
            ]
        )
            // TODO: Remove this once atomic db constraint removed
        ->then(function (Batch $batch) use ($floatingIp) {
            $floatingIp->deleted = time();
            $floatingIp->saveQuietly();
        })->dispatch();
    }
}

<?php

namespace App\Jobs\Task\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Jobs\Kingpin\Volume\UndeployCheck;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use TaskableBatch;

    /** @var Task */
    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        $this->deleteTaskBatch([
            [
                new Undeploy($this->task->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}

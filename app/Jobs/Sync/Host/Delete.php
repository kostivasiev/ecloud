<?php

namespace App\Jobs\Task\Host;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use TaskableBatch;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        $host = $this->task->resource;

        // TODO
//        $this->deleteTaskBatch([
//        ])->dispatch();

        // TODO delete this when we implement deleteTaskBatch
        $this->task->completed = true;
        $this->task->save();
        $this->task->resource->delete();

        Log::info(get_class($this) . ' : Finished', ['id' => $host->id]);
    }
}

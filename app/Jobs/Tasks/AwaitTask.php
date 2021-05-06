<?php

namespace App\Jobs\Tasks;

use App\Jobs\Job;
use App\Models\V2\Task;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class AwaitTask extends Job
{
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->resource->id]);

        $task = $this->task;

        if (empty($task->data['task_id'])) {
            throw new \Exception("task_id must be provided");
        }

        $taskToCheck = Task::findOrFail($task->data['task_id']);

        Bus::batch([
            [
                new AwaitTaskJob($taskToCheck),
            ]
        ])->then(function (Batch $batch) use ($task) {
            Log::info("Setting task completed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->completed = true;
            $task->save();
        })->catch(function (Batch $batch, Throwable $e) use ($task) {
            Log::warning("Setting task failed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->failure_reason = $e->getMessage();
            $task->save();
        })->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->resource->id]);
    }
}

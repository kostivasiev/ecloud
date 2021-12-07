<?php

namespace App\Listeners\V2;

use App\Support\Sync;
use App\Tasks\Task;
use Illuminate\Support\Facades\Log;

class SyncDeleteTaskComplete
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $task = $event->model;
        $taskJob = new $task->job($task);

        if (!($taskJob instanceof Task)) {
            return;
        }

        if ($task->name === Sync::TASK_NAME_DELETE && $task->status === \App\Models\V2\Task::STATUS_COMPLETE) {
            Log::debug('Deleting resource', ['task_id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->resource->delete();
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}

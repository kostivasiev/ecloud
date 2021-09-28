<?php

namespace App\Jobs\Tasks;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitTasks extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private Task $task;
    private $taskDataKey;

    public function __construct(Task $task, $taskDataKey)
    {
        $this->task = $task;
        $this->taskDataKey = $taskDataKey;
    }

    public function handle()
    {
        if (empty($this->task->data[$this->taskDataKey])) {
            Log::debug('No tasks to await, skipping');
            return;
        }

        foreach ($this->task->data[$this->taskDataKey] as $taskID) {
            $task = Task::findOrFail($taskID);
            if ($task->status == Task::STATUS_FAILED) {
                Log::error(get_class($this) . ': Task in failed state, abort', ['id' => $this->task->resource->id, 'task_id' => $task->id]);
                $this->fail(new \Exception("Task {$task->id} in failed state, abort"));
                return;
            }

            if ($task->status != Task::STATUS_COMPLETE) {
                Log::warning(get_class($this) . ': Task not complete, retrying in ' . $this->backoff . ' seconds', ['id' => $this->task->resource->id, 'task_id' => $task->id]);
                $this->release($this->backoff);
                return;
            }
        }
    }

    public function resolveModelId()
    {
        return $this->task->resource->id;
    }
}

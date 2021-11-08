<?php
namespace App\Traits\V2\Jobs;

use App\Models\V2\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait AwaitTask
{
    public $tries = 60;

    public $backoff = 5;

    public function awaitTask(Task $task, $timeoutSeconds = 600, $sleep = 10)
    {
        $end = Carbon::now()->addSeconds($timeoutSeconds);

        Log::info(get_class($this) . ': Waiting for task to complete... ', ['id' => $this->model->id, 'resource' => $task->id]);

        do {
            $task->refresh();

            if ($task->completed == true) {
                Log::info(get_class($this) . ': Waiting for task to complete - COMPLETED', ['id' => $this->model->id, 'resource' => $task->id]);
                return true;
            }

            if (!empty($task->failure_reason)) {
                Log::error(get_class($this) . ': Task in failed state, abort', ['id' => $this->model->id, 'resource' => $task->id]);
                $this->fail(new \Exception("Task '" . $task->id . "' in failed state"));
                return false;
            }

            Log::info(get_class($this) . ': Waiting for task to complete - task is not ready yet, trying again in  ' . $sleep . ' seconds.', ['id' => $this->model->id, 'resource' => $task->id]);
            sleep($sleep);
        } while (Carbon::now() < $end);

        Log::error(get_class($this) . ': Timed out waiting for task to complete', ['id' => $this->model->id, 'resource' => $task->id]);
        $this->fail(new \Exception('Timed out waiting for task ' . $task->id . 'to complete'));
        return false;
    }

    public function awaitTaskWithRelease(Task $task)
    {
        $task->refresh();

        if ($task->status == Task::STATUS_FAILED) {
            Log::error(get_class($this) . ': Task in failed state, abort', ['id' => $this->model->id, 'resource' => $task->id]);
            $this->fail(new \Exception("Task '" . $task->id . "' in failed state"));
            return;
        }

        if ($task->status != Task::STATUS_COMPLETE) {
            Log::warning(get_class($this) . ': Task not completed, retrying in ' . $this->backoff . ' seconds', ['id' => $task->id]);
            $this->release($this->backoff);
            return;
        }
    }

    public function awaitTasks(Array $taskIds = [])
    {
        foreach ($taskIds as $id) {
            $task = Task::findOrFail($id);
            $this->awaitTaskWithRelease($task);
        }
    }
}





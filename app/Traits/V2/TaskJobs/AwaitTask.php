<?php
namespace App\Traits\V2\TaskJobs;

use App\Models\V2\Task;
use Carbon\Carbon;

trait AwaitTask
{
    public function awaitTask(Task $task, $timeoutSeconds = 600, $sleep = 10)
    {
        $end = Carbon::now()->addSeconds($timeoutSeconds);

        $this->info('Waiting for task to complete... ', ['target_resource' => $task->id]);

        do {
            $task->refresh();

            if ($task->completed == true) {
                $this->info('Waiting for task to complete - COMPLETED', ['target_resource' => $task->id]);
                return true;
            }

            if (!empty($task->failure_reason)) {
                $this->error('Task in failed state, abort', ['target_resource' => $task->id]);
                $this->fail(new \Exception("Task '" . $task->id . "' in failed state"));
                return false;
            }

            $this->info('Waiting for task to complete - task is not ready yet, trying again in  ' . $sleep . ' seconds.', ['target_resource' => $task->id]);
            sleep($sleep);
        } while (Carbon::now() < $end);

        $this->error('Timed out waiting for task to complete', ['target_resource' => $task->id]);
        $this->fail(new \Exception('Timed out waiting for task ' . $task->id . 'to complete'));
        return false;
    }

    public function awaitTaskWithRelease(Task $task, $backoff = 10)
    {
        $task->refresh();

        if ($task->completed == true) {
            $this->info('Waiting for task to complete - COMPLETED', ['target_resource' => $task->id]);
            return true;
        }

        if (!empty($task->failure_reason)) {
            $this->error('Task in failed state, abort', ['target_resource' => $task->id]);
            $this->fail(new \Exception("Task '" . $task->id . "' in failed state"));
            return false;
        }

        $this->release($backoff);
        return true;
    }

    public function awaitTasks(Array $taskIds = [])
    {
        foreach ($taskIds as $id) {
            $task = Task::findOrFail($id);
            $this->awaitTaskWithRelease($task);
        }
    }
}

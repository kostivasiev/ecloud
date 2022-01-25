<?php
namespace App\Traits\V2\TaskJobs;

use App\Models\V2\Task;

trait AwaitTask
{
    public function awaitTaskWithRelease(...$tasks)
    {
        $backoff = $this->backoff ?? 5;

        $incompleteTaskIDs = [];
        foreach ($tasks as $task) {
            $task->refresh();

            if ($task->status == Task::STATUS_FAILED) {
                $this->error("Task {$task->id} in failed state");
                $this->fail(new \Exception("Task {$task->id} in failed state"));
                return false;
            }

            if ($task->status != Task::STATUS_COMPLETE) {
                $incompleteTaskIDs[] = $task->id;
            }
        }

        if (count($incompleteTaskIDs) > 0) {
            $taskStr = implode(', ', $incompleteTaskIDs);
            $this->debug("Task(s) {$taskStr} not complete, retrying in {$backoff} seconds");
            $this->release($backoff);
            return true;
        }
    }
}

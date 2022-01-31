<?php
namespace App\Traits\V2\TaskJobs;

use App\Models\V2\Task;

trait AwaitTask
{
    public $tries = 60;

    public $backoff = 5;

    public function awaitTaskWithRelease(...$tasks)
    {
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
            $this->debug("Task(s) {$taskStr} not complete, retrying in {$this->backoff} seconds");
            $this->release($this->backoff);
            return true;
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

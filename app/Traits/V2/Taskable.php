<?php

namespace App\Traits\V2;

use App\Exceptions\V2\TaskException;
use App\Models\V2\ResellerScopeable;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;

trait Taskable
{
    public function tasks()
    {
        return $this->morphMany(Task::class, 'resource');
    }


    /**
     * Attempts to obtain an exclusive task lock whilst validating that a new task can be created
     *
     * @param $callback
     * @return mixed
     * @throws TaskException
     */
    public function withTaskLock($callback)
    {
        $lock = Cache::lock($this->getTaskLockKey(), 60);

        try {
            Log::debug(get_class($this) . ' : Attempting to obtain task lock for 60s', ['resource_id' => $this->id]);
            $lock->block(60);

            if (!$this->canCreateTask()) {
                throw new TaskException();
            }

            return $callback($this);
        } finally {
            $lock->release();
        }
    }

    protected function getTaskLockKey()
    {
        return 'task.' . $this->id;
    }

    public function canCreateTask()
    {
        if ($this->tasks->filter(function ($task) {
                return $task->status == Task::STATUS_INPROGRESS;
        })->count()) {
            Log::warning(get_class($this) . ' : Cannot create task, resource has task in progress', ['resource_id' => $this->id, 'count'=>$this->tasks->filter(function ($task) {
                return $task->status == Task::STATUS_INPROGRESS;
            })->count() ]);
            return false;
        }

        return true;
    }

    public function createTask($name, $job, $data = null, $queued = true)
    {
        Log::debug(get_class($this) . ' : Creating new task - Started', [
            'resource_id' => $this->id,
        ]);

        $task = app()->make(Task::class);
        $task->resource()->associate($this);
        $task->completed = false;
        $task->name = $name;
        $task->job = $job;
        $task->data = $data;
        if ($this instanceof ResellerScopeable) {
            $task->reseller_id = $this->getResellerId();
        }
        $task->save();

        Log::debug(get_class($this) . ' : Creating new task - Finished', [
            'resource_id' => $this->id,
        ]);

        return $task;
    }

    public function createTaskWithLock($name, $job, $data = null)
    {
        return $this->withTaskLock(function ($model) use ($name, $job, $data) {
            return $this->createTask($name, $job, $data);
        });
    }
}

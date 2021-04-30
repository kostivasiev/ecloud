<?php

namespace App\Traits\V2;

use App\Models\V2\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Taskable
{
    public function tasks()
    {
        return $this->morphMany(Task::class, 'resource');
    }

    public function withTaskLock($callback)
    {
        $lock = Cache::lock($this->getTaskLockKey(), 60);

        try {
            Log::debug(get_class($this) . ' : Attempting to obtain task lock for 60s', ['resource_id' => $this->id]);
            $lock->block(60);

            $callback($this);
        } finally {
            $lock->release();
        }
    }

    public function getTaskLockKey()
    {
        return 'task.' . $this->id;
    }

    public function canCreateTask()
    {
        if ($this->tasks->filter(function ($task) {
                return $task->status == Task::STATUS_INPROGRESS;
        })->count()) {
            Log::warning(get_class($this) . ' : Cannot create task, resource has task in progress', ['resource_id' => $this->id]);
            return false;
        }

        return true;
    }

    public function createTask($name, $job, $data = null)
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
        $task->save();

        Log::debug(get_class($this) . ' : Creating new task - Finished', [
            'resource_id' => $this->id,
        ]);

        return $task;
    }

    public function getTaskAttribute()
    {
        $status = 'unknown';
        $name = 'unknown';

        if ($this->tasks()->count()) {
            $latest = $this->tasks()->latest()->first();

            $status = $latest->status;
            $name   = $latest->name;
        }

        return (object) [
            'status' => $status,
            'name' => $name,
        ];
    }
}

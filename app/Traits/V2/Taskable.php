<?php

namespace App\Traits\V2;

use App\Models\V2\Task;
use Exception;

/**
 * Trait Taskable
 * @package App\Traits\V2
 */
trait Taskable
{
    /**
     * @return bool
     */
    public function getTaskRunningAttribute()
    {
        return $this->tasks()->get()->filter(function ($task) {
            return !$task->is_ended;
        })->count() > 0;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'resource_id', 'id');
    }

    // Until tasks are exposed, for now we'll compute status based on tasks associated
    // with this resource.
    // If a resource has at least one task, and the latested task failed, the status will be STATUS_FAILED
    // If a resource has at least one task running, the status will be STATUS_PROVISIONING
    // else status will be STATUS_READY
    public function getStatusAttribute()
    {
        if ($this->tasks()->count() > 0 && $this->tasks()->latest()->first()->is_failed) {
            return self::STATUS_FAILED;
        }

        if ($this->task_running) {
            return self::STATUS_PROVISIONING;
        }

        return self::STATUS_READY;
    }

    public function createTask()
    {
        if ($this->task_running) {
            throw new Exception('Task already running for resource');
        }

        return $this->tasks()->create();
    }
}

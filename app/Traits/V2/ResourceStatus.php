<?php

namespace App\Traits\V2;

use App\Models\V2\TaskJobStatus;

trait ResourceStatus
{
    // Until tasks are exposed, for now we'll compute status based on tasks associated
    // with this resource.
    // If a resource only has 1 task and that task is failed, the status will be STATUS_FAILED
    // If a resource only has 1 task and that task is not completed, the status will be STATUS_CREATING
    // If a resource has one or more tasks that are running, the status will be STATUS_UPDATING
    // else status will be STATUS_READY
    public function getStatusAttribute()
    {
        if ($this->tasks()->count() == 1 && $this->tasks()->latest()->first()->is_failed) {
            return self::STATUS_FAILED;
        }

        if ($this->tasks()->count() == 1 && !$this->tasks()->latest()->first()->is_ended) {
            return self::STATUS_CREATING;
        }

        if ($this->task_running) {
            return self::STATUS_UPDATING;
        }

        return self::STATUS_READY;
    }
}
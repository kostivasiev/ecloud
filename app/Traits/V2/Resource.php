<?php

namespace App\Traits\V2;

use App\Models\V2\Task;

trait Resource
{
    public function getTaskRunningAttribute()
    {
        return $this->tasks()->whereNotIn("status", Task::TASK_FINISHED_STATUSES)->count() > 0;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, "resource_id", "id");
    }
}
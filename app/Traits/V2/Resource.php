<?php

namespace App\Traits\V2;

use App\Models\V2\Task;
use App\Models\V2\TaskJobStatus;

trait Resource
{
    public function getTaskRunningAttribute()
    {
        return $this->tasks()->with(["jobs" => function($q)
        {
            $q->whereNotIn("status", TaskJobStatus::TASK_FINISHED_STATUSES);
        }])->count();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, "resource_id", "id");
    }
}
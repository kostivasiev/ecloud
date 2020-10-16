<?php

namespace App\Traits\V2;

use App\Models\V2\Task;

trait ResourceStatus
{
    // Until tasks are exposed, for now we'll compute status based on tasks associated
    // with this resource.
    // If an resource only has 1 task and that task is failed, the status will be STATUS_FAILED
    // If an resource only has 1 task and that task is not completed, the status will be STATUS_CREATING
    public function getStatusAttribute()
    {
        if ($this->tasks()->count() == 1 && $this->tasks()->latest()->first()->status == Task::STATUS_FAILED) {
            return self::STATUS_FAILED;
        }

        if ($this->tasks()->count() == 1 && !$this->tasks()->latest()->first()->getIsEndedAttribute()) {
            return self::STATUS_CREATING;
        }

        if ($this->tasks()->count() > 1 && !$this->tasks()->latest()->first()->getIsEndedAttribute()) {
            return self::STATUS_UPDATING;
        }

        return self::STATUS_READY;
    }
}
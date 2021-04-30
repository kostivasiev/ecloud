<?php

namespace App\Support;

use App\Models\V2\Task;

class Sync
{
    const STATUS_INPROGRESS = Task::STATUS_INPROGRESS;
    const STATUS_FAILED     = Task::STATUS_FAILED;
    const STATUS_COMPLETE   = Task::STATUS_COMPLETE;

    const TASK_NAME_UPDATE = 'sync_update';
    const TASK_NAME_DELETE = 'sync_delete';

    public function transformTaskNameToType($name)
    {
        return ltrim($name, 'sync_');
    }
}
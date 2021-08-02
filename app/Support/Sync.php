<?php

namespace App\Support;

use App\Models\V2\Task;

class Sync
{
    const STATUS_INPROGRESS = Task::STATUS_INPROGRESS;
    const STATUS_FAILED     = Task::STATUS_FAILED;
    const STATUS_COMPLETE   = Task::STATUS_COMPLETE;

    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    const TASK_NAME_UPDATE = 'sync_update';
    const TASK_NAME_DELETE = 'sync_delete';

    public static function transformTaskNameToType($name)
    {
        switch ($name) {
            case static::TASK_NAME_UPDATE:
                return static::TYPE_UPDATE;
            case static::TASK_NAME_DELETE:
                return static::TYPE_DELETE;
            default:
                return $name;
        }
    }
}

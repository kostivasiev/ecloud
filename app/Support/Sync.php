<?php

namespace App\Support;

class Sync
{
    const STATUS_INPROGRESS = 'in-progress';
    const STATUS_FAILED     = 'failed';
    const STATUS_COMPLETE   = 'complete';

    const TASK_NAME_UPDATE = 'sync_update';
    const TASK_NAME_DELETE = 'sync_delete';

    public function transformTaskNameToType($name)
    {
        return ltrim($name, 'sync_');
    }
}
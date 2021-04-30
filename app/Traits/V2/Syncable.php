<?php

namespace App\Traits\V2;

use App\Exceptions\TaskException;
use App\Listeners\V2\ResourceSyncSaving;
use App\Support\Sync;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait Syncable
{
    use Taskable;

    // TODO: Make this abstract - we should force objects implementing Syncable to return job class
    public function getUpdateSyncJob()
    {
        $class = explode('\\', __CLASS__);
        return 'App\\Jobs\\Sync\\' . end($class) . '\\Update';
    }

    // TODO: Make this abstract - we should force objects implementing Syncable to return job class
    public function getDeleteSyncJob()
    {
        $class = explode('\\', __CLASS__);
        return 'App\\Jobs\\Sync\\' . end($class) . '\\Delete';
    }

    public function getSyncAttribute()
    {
        $status = 'unknown';
        $type = 'unknown';

        if ($this->tasks()->count()) {
            $latest = $this->tasks()
                ->where(function ($query) {
                    return $query
                        ->where('name', '=', Sync::TASK_NAME_UPDATE)
                        ->orWhere('name', '=', Sync::TASK_NAME_DELETE);
                })
                ->latest()
                ->first();

            if ($latest) {
                $status = $latest->status;
                $type   = $latest->name;
            }
        }

        return (object) [
            'status' => $status,
            'type' => $type,
        ];
    }
}

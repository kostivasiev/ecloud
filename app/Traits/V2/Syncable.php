<?php

namespace App\Traits\V2;

use App\Exceptions\V2\TaskException;
use App\Support\Sync;

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
                $type   = Sync::transformTaskNameToType($latest->name);
            }
        }

        return (object) [
            'status' => $status,
            'type' => $type,
        ];
    }

    public function createSync($type, $data = null)
    {
        switch ($type) {
            case Sync::TYPE_UPDATE:
                return $this->createTask(Sync::TASK_NAME_UPDATE, $this->getUpdateSyncJob(), $data);
            case Sync::TYPE_DELETE:
                return $this->createTask(Sync::TASK_NAME_DELETE, $this->getDeleteSyncJob(), $data);
        }
        return false;
    }

    public function syncSave($data = null)
    {
        return $this->withTaskLock(function ($model) use ($data) {
            if (!$model->canCreateTask()) {
                throw new TaskException();
            }
            $model->save();
            return $this->createSync(Sync::TYPE_UPDATE, $data);
        });
    }

    public function syncDelete($data = null)
    {
        return $this->withTaskLock(function ($model) use ($data) {
            if (!$model->canCreateTask()) {
                throw new TaskException();
            }
            return $this->createSync(Sync::TYPE_DELETE, $data);
        });
    }
}

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
        $syncJobClass = 'App\\Jobs\\Sync\\' . end($class) . '\\Update';
        if (class_exists($syncJobClass)) {
            return $syncJobClass;
        }

        return 'App\\Tasks\\'. end($class) . '\\Update';
    }

    // TODO: Make this abstract - we should force objects implementing Syncable to return job class
    public function getDeleteSyncJob()
    {
        $class = explode('\\', __CLASS__);
        $syncJobClass = 'App\\Jobs\\Sync\\' . end($class) . '\\Delete';
        if (class_exists($syncJobClass)) {
            return $syncJobClass;
        }

        return 'App\\Tasks\\'. end($class) . '\\Delete';
    }

    public function getSyncAttribute()
    {
        $status = Sync::STATUS_COMPLETE;
        $type = 'n/a';

        if ($this->tasks()->count()) {
            $latest = $this->tasks()->latest()->first();

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
            $model->save();
            return $this->createSync(Sync::TYPE_UPDATE, $data);
        });
    }

    public function syncDelete($data = null)
    {
        return $this->withTaskLock(function ($model) use ($data) {
            return $this->createSync(Sync::TYPE_DELETE, $data);
        });
    }
}

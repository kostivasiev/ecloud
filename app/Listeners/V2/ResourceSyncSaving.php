<?php

namespace App\Listeners\V2;

use App\Jobs\Sync\Completed;
use App\Models\V2\Sync;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaving
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $model = $event->model;

        if ($model->id === null) {
            Log::warning(get_class($this) . ' : Creating resource, nothing to do', ['resource_id' => $model->id]);
            return true;
        }

        $lock = Cache::lock("sync." . $model->id, 60);
        try {
            if (!$lock->get()) {
                Log::error(get_class($this) . ' : Update blocked, cannot obtain sync lock', ['resource_id' => $model->id]);
            }

            if ($model->syncs()->count() == 1 && $model->getStatus() === Sync::STATUS_FAILED) {
                Log::warning(get_class($this) . ' : Update blocked, resource has a single failed sync', ['resource_id' => $model->id]);
                return false;
            }

            if ($model->getStatus() === Sync::STATUS_INPROGRESS) {
                Log::warning(get_class($this) . ' : Update blocked, resource has outstanding sync', ['resource_id' => $model->id]);
                return false;
            }
        } finally {
            $lock->release();
        }

        Log::debug(get_class($this) . ' : Finished', ['resource_id' => $model->id]);
    }
}

<?php

namespace App\Listeners\V2;

use App\Exceptions\SyncException;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaving
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        if ($event->model->id === null) {
            Log::warning(get_class($this) . ' : Creating resource, nothing to do', ['resource_id' => $event->model->id]);
            return true;
        }

        $lock = Cache::lock($event->model->syncGetLockKey(), 60);
        try {
            Log::debug(get_class($this) . ' : Attempting to obtain lock for 60s', ['resource_id' => $event->model->id]);
            $lock->block(60);
            Log::debug(get_class($this) . ' : Lock obtained', ['resource_id' => $event->model->id]);

            Cache::put("sync_saving_lock." . $event->model->id, $lock->owner(), 60);

            if (!$event->model->canSync(Sync::TYPE_UPDATE)) {
                throw new SyncException("Cannot sync");
            }
        } catch (\Exception $e) {
            Cache::forget("sync_saving_lock." . $event->model->id);
            $lock->release();
            throw $e;
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

<?php

namespace App\Listeners\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaved
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $event->model->createSync(Sync::TYPE_UPDATE);

        $existingLock = Cache::pull("sync_saving_lock." . $event->model->id);
        if ($existingLock) {
            Log::debug(get_class($this) . ' : Releasing existing sync lock', ['resource_id' => $event->model->id, 'lock_owner' => $existingLock]);
            Cache::restoreLock($event->model->syncGetLockKey(), $existingLock)->release();
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

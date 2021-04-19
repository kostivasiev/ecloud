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

        $model = $event->model;

        if ($model->id === null) {
            Log::warning(get_class($this) . ' : Creating resource, nothing to do', ['resource_id' => $model->id]);
            return true;
        }

        $lock = $model->syncLock();

        if (!$model->canSync(Sync::TYPE_UPDATE)) {
            $lock->release();
            throw new SyncException("Cannot sync");
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $model->id]);
    }
}

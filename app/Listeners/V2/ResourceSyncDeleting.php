<?php

namespace App\Listeners\V2;

use App\Exceptions\SyncException;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResourceSyncDeleting
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $model = $event->model;

        $lock = $model->syncLock();

        if (($model->sync->status === Sync::STATUS_COMPLETE) && $model->sync->type == Sync::TYPE_DELETE) {
            Log::info(get_class($this) . ' : Delete sync complete, not blocking deletion', ['resource_id' => $model->id]);
            $lock->release();
            return true;
        }

        $model->createSync(Sync::TYPE_DELETE);

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $model->id]);
        return false;
    }
}

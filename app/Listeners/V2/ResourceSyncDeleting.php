<?php

namespace App\Listeners\V2;

use App\Exceptions\SyncException;
use App\Jobs\Sync\Completed;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResourceSyncDeleting
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $model = $event->model;

        if (($model->getStatus() === Sync::STATUS_COMPLETE) && $model->getSyncDeleting()) {
            Log::info(get_class($this) . ' : Delete sync complete, not blocking deletion', ['resource_id' => $model->id]);
            return true;
        }

        $lock = Cache::lock("sync." . $model->id, 60);
        try {
            $lock->block(60);

            if ($model->getStatus() === Sync::STATUS_INPROGRESS) {
                Log::warning(get_class($this) . ' : Delete blocked, resource has outstanding sync', ['resource_id' => $model->id]);
                throw new SyncException("Outstanding sync");
            }

            if (!$model->createSync(Sync::TYPE_DELETE)) {
                Log::error(get_class($this) . ' : Failed to create sync for delete', ['resource_id' => $model->id]);
                throw new SyncException("Failed to create sync");
            }
        } catch (LockTimeoutException $e) {
            Log::error(get_class($this) . ' : Delete blocked, cannot obtain sync lock', ['resource_id' => $model->id]);
            throw new SyncException("Cannot obtain sync lock");
        } finally {
            $lock->release();
        }

        Log::debug(get_class($this) . ' : Finished', ['resource_id' => $model->id]);
        return false;
    }
}

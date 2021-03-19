<?php

namespace App\Listeners\V2;

use App\Jobs\Sync\Completed;
use App\Jobs\Sync\Update;
use App\Models\V2\Sync;
use App\Models\V2\TestSyncable;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncCreated
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        // TODO: Remove following once all syncable resources are using update/delete functionality
        if (!in_array(get_class($event->model->resource), [
            TestSyncable::class,
            Volume::class,
        ])) {
            return true;
        }

        $syncJob = false;
        switch ($event->model->type) {
            case Sync::TYPE_UPDATE:
                $syncJob = $event->model->resource->getUpdateSyncJob();
                break;
            case Sync::TYPE_DELETE:
                $syncJob = $event->model->resource->getDeleteSyncJob();
                break;
        }

        if ($syncJob) {
            Log::debug("Dispatching job", ["job" => $syncJob]);
            dispatch(new $syncJob($event->model));
        }

        Log::debug(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

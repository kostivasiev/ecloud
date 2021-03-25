<?php

namespace App\Listeners\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSync
{
    // Old - replaced with Deleting/Saved/Saving events
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $model = $event->model;

        if ($model->id === null) {
            Log::warning(get_class($this) . ' : Creating resource, nothing to do', ['resource_id' => $model->id]);
            return true;
        }

        if ($model->getStatus() === 'failed') {
            Log::warning(get_class($this) . ' : Save blocked, resource has failed sync', ['resource_id' => $model->id]);
            return false;
        }

        if ($model->getStatus() !== 'complete') {
            Log::warning(get_class($this) . ' : Save blocked, resource has outstanding sync', ['resource_id' => $model->id]);
            return false;
        }

        $model->createSync();

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $model->id]);
    }
}

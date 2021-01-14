<?php

namespace App\Listeners\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSync
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $model = $event->model;

        if ($model->id === null) {
            Log::warning(get_class($this) . ' : Creating resource, nothing to do', ['event' => $event]);
            return true;
        }

        if ($model->getStatus() === 'failed') {
            Log::warning(get_class($this) . ' : Save blocked, resource has failed sync', ['event' => $event]);
            return false;
        }

        if ($model->getStatus() !== 'complete') {
            Log::warning(get_class($this) . ' : Save blocked, resource has outstanding sync', ['event' => $event]);
            return false;
        }

        $model->createSync();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

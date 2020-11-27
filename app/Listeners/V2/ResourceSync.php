<?php

namespace App\Listeners\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSync
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        if (!$event->model->getSyncCompleted()) {
            Log::warning(get_class($this) . ' : Save blocked, resource has outstanding sync', ['event' => $event]);
            return false;
        }

        if ($event->model->getSyncFailed()) {
            Log::warning(get_class($this) . ' : Save blocked, resource has failed sync', ['event' => $event]);
            return false;
        }

        if ($event->model->id === null) {
            // Hack to generate an ID
            $event->model::addCustomKey($event->model);
        }

        $sync = app()->make(Sync::class);
        $sync->resource_id = $event->model->id;
        $sync->completed = false;
        $sync->save();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

<?php

namespace App\Listeners\V2;

use App\Exceptions\V2\TaskException;
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

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

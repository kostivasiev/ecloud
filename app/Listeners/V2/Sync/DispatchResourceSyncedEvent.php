<?php

namespace App\Listeners\V2\Sync;

use App\Events\V2\Sync\Saved;
use App\Models\V2\Volume;
use App\Support\Resource;
use Illuminate\Support\Facades\Log;

class DispatchResourceSyncedEvent
{
    public function handle(Saved $event)
    {

        if (!$event->model->completed) {
            return;
        }

        $resource = Resource::classFromId($event->model->resource_id)::findOrFail($event->model->resource_id);

        if ($resource instanceof Volume) {
            event(new \App\Events\V2\Volume\Synced($resource));
            Log::notice('volume sync event dispatched');
            return;
        }
    }
}

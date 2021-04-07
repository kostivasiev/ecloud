<?php

namespace App\Listeners\V2;

use App\Jobs\Sync\Update;
use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaved
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        if (!$event->model->createSync(Sync::TYPE_UPDATE)) {
            Log::error(get_class($this) . ' : Failed to create sync for update', ['resource_id' => $event->model->id]);
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

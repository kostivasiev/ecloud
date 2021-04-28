<?php

namespace App\Listeners\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaved
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $event->model->createSync(Sync::TYPE_UPDATE);

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

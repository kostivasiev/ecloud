<?php

namespace App\Listeners\V2;

use App\Jobs\Sync\Router\Update;
use App\Support\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSyncSaved
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        $event->model->createTask('sync_update', $event->model->getUpdateSyncJob());

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
    }
}

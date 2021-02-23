<?php

namespace App\Listeners\V2\InstanceVolume;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class MarkSyncing
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        $instance = Instance::find($event->model->instance_id);
        if (!$instance) {
            Log::error(get_class($this) . ' : Failed to find instance');
            return false;
        }

        $volume = Volume::find($event->model->volume_id);
        if (!$volume) {
            Log::error(get_class($this) . ' : Failed to find volume');
            return false;
        }

        if (!$instance->createSync()) {
            Log::error(get_class($this) . ' : Failed to create sync for instance');
            return false;
        }

        if (!$volume->createSync()) {
            Log::error(get_class($this) . ' : Failed to create sync for volume');
            $instance->markSyncCompleted();
            return false;
        }

        Log::info(get_class($this) . ' : Finished', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        return true;
    }
}

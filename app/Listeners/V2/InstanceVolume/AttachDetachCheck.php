<?php

namespace App\Listeners\V2\InstanceVolume;

use App\Exceptions\V2\TaskException;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class AttachDetachCheck
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        $instance = Instance::findOrFail($event->model->instance_id);
        $volume = Volume::findOrFail($event->model->volume_id);

        if (!$instance->canCreateTask() || !$volume->canCreateTask()) {
            throw new TaskException();
        }

        Log::info(get_class($this) . ' : Finished', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        return true;
    }
}

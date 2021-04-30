<?php

namespace App\Listeners\V2\InstanceVolume;

use App\Events\V2\InstanceVolume\Created;
use App\Jobs\Kingpin\Volume\Attach as AttachJob;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Sync\Completed;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class VolumeAttach
{
    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        $volume = Volume::findOrFail($event->model->volume_id);
        $instance = Instance::findOrFail($event->model->instance_id);

        $task = $volume->createTask('volume_attach', \App\Jobs\Tasks\Volume\VolumeAttach::class, ['instance_id' => $event->model->instance_id]);
        $instance->createTask('volume_attach_wait', \App\Jobs\Tasks\AwaitTask::class, ['task_id' => $task->id]);

        Log::info(get_class($this) . ' : Finished', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        return true;
    }
}

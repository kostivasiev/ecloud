<?php

namespace App\Listeners\V2\InstanceVolume;

use App\Events\V2\InstanceVolume\Created;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Sync\Completed;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class UpdateIops
{
    public function handle(Created $event)
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

        $jobs = [
            new IopsChange($volume),
            new Completed($volume),
            new Completed($instance),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', [
            'instance_id' => $event->model->instance_id,
            'volume_id' => $event->model->volume_id,
        ]);

        return true;
    }
}

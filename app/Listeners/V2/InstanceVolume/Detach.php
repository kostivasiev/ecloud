<?php

namespace App\Listeners\V2\InstanceVolume;

use App\Events\V2\InstanceVolume\Created;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Kingpin\Volume\MarkSyncCompleted;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class Detach
{
    const RETRY_DELAY = 5;

    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $instance = Instance::find($event->model->instance_id);
        if (!$instance) {
            return;
        }

        $volume = Volume::find($event->model->volume_id);
        if (!$volume) {
            return;
        }

        if (!$volume->createSync()) {
            $this->release(static::RETRY_DELAY);
            Log::info(
                'Waiting for ' . $this->model->id . ' syncing, retrying in ' . static::RETRY_DELAY . ' seconds'
            );
            return;
        }

        $jobs = [
            new IopsChange($volume),
            new MarkSyncCompleted($volume),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

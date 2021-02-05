<?php

namespace App\Jobs\Volume;

use App\Events\V2\Volume\Saved;
use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class CapacityIncrease extends Job
{
    private Saved $event;

    public function __construct(Saved $event)
    {
        $this->event = $event;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['event' => $this->event]);

        $volume = $this->event->model;
        if ($volume->capacity > $this->event->originalCapacity) {
            $endpoint = '/api/v1/vpc/' . $volume->vpc_id . '/volume/' . $volume->vmware_uuid . '/size';

            if ($volume->instances()->count() > 0) {
                $instance = $volume->instances()->first();
                $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size';
            }

            $volume->availabilityZone->kingpinService()->put(
                $endpoint,
                [
                    'json' => [
                        'sizeGiB' => $volume->capacity
                    ]
                ]
            );

            Log::info('Volume ' . $volume->getKey() . ' capacity increased from ' . $this->event->originalCapacity . ' to ' . $volume->capacity);
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $this->event]);
    }
}

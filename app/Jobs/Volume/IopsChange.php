<?php

namespace App\Jobs\Volume;

use App\Events\V2\Volume\Saved;
use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class IopsChange extends Job
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
        if ($volume->iops !== $this->event->originalIops) {
            if ($volume->instances()->count() > 0) {
                $instance = $volume->instances()->first();
                $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/iops';

                $volume->availabilityZone->kingpinService()->put(
                    $endpoint,
                    [
                        'json' => [
                            'limit' => $volume->iops
                        ]
                    ]
                );

                Log::info('Volume ' . $volume->getKey() . ' iops changed from ' . $this->event->originalIops . ' to ' . $volume->iops);
            }
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $this->event]);
    }
}

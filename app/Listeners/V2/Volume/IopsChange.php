<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Saved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class IopsChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Saved $event
     * @return void
     * @throws \Exception
     */
    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $volume = $event->model;
        if ($volume->iops !== $event->originalIops) {
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

                Log::info('Volume ' . $volume->getKey() . ' iops changed from ' . $event->originalIops . ' to ' . $volume->iops);
            }
        }

        $volume->setSyncCompleted();
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

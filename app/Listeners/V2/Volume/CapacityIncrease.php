<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Updated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CapacityIncrease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  Updated  $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        $volume = $event->volume;
        if ($volume->capacity > $event->originalCapacity) {
            $endpoint = '/api/v1/vpc/' . $volume->vpc_id . '/volume/' . $volume->vmware_uuid . '/size';

            if ($volume->instances()->count() > 0) {
                $instance = $volume->instances()->first();
                $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size';
            }

            try {
                $volume->availabilityZone->kingpinService()->put(
                    $endpoint,
                    [
                        'json' => [
                            'sizeGiB' => $volume->capacity
                        ]
                    ]
                );
            } catch (GuzzleException $exception) {
                throw new \Exception($exception->getResponse()->getBody()->getContents());
            }

            Log::info('Volume ' . $volume->getKey() . ' capacity increased from ' . $event->originalCapacity . ' to ' . $volume->capacity);
        }
    }
}

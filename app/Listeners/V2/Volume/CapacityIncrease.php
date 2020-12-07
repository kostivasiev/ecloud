<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Updated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CapacityIncrease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

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
            } catch (\Exception $exception) {
                $message = 'Unhandled error for ' . $volume->id;
                Log::error($message, [$exception]);
                $volume->setSyncFailureReason($message . ' : ' . $exception->getMessage());
                throw $exception;
            }

            Log::info('Volume ' . $volume->getKey() . ' capacity increased from ' . $event->originalCapacity . ' to ' . $volume->capacity);
        }

        $volume->setSyncCompleted();
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

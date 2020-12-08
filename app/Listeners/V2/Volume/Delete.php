<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Deleted;
use App\Events\V2\Volume\Updated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Delete implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this).' : Started', ['event' => $event]);
        $volume = $event->model;

        if ($volume->instances()->count() == 0) {
            try {
                $volume->availabilityZone->kingpinService()->delete(
                    '/api/v1/vpc/' . $volume->vpc->id . '/volume/' . $volume->vmware_uuid
                );
            } catch (\Exception $exception) {
                $message = 'Unhandled error for ' . $volume->id;
                Log::error($message, [$exception]);
                $volume->setSyncFailureReason($message . ' : ' . json_decode($exception->getBody()->getContents()));
                throw $exception;
            }

            Log::info('Volume ' . $volume->getKey() . ' (' . $volume->vmware_uuid . ') deleted.');
        }

        $volume->setSyncCompleted();
        Log::info(get_class($this).' : Finished', ['event' => $event]);
    }
}

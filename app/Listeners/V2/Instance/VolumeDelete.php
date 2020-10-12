<?php

namespace App\Listeners\V2;

use App\Events\V2\Instance\Deleted;
use App\Models\V2\Instance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InstanceVolumeDelete implements ShouldQueue
{
    use InteractsWithQueue;

    // Allows for 3 attempts with 10 seconds between
    public $tries = 3;
    public $delay = 10;

    public function handle(Deleted $event)
    {
        $instance = $event->model;
        Log::info('Attempting to Delete volumes for instance ' . $instance->getKey());
        if ($this->attempts() <= $this->tries) {
            if (!is_null(Instance::withTrashed()->findOrFail($instance->id)->deleted_at)) {
                $instance->volumes()->each(function ($volume) use ($instance) {
                    // If volume is only used in this instance then delete
                    if ($volume->instances()->count() == 0) {
                        Log::info('Deleting volume: ' . $volume->getKey());
                        $volume->delete();
                        $volume->instances()->detach($instance);
                    }
                });
            } else {
                Log::info(
                    'Instance '.$instance->getKey().' not yet deleted. Deferring volume deletion for '.
                    $this->delay.' seconds'
                );
                $this->release($this->delay);
            }
        } else {
            $this->fail(new \Exception(
                'Failed to Delete volumes for Instance '.$instance->getKey().' : '.
                'maximum number of retries exceeded'
            ));
            $this->delete(); // releases the job if the number of retries has been exceeded
        }
    }
}

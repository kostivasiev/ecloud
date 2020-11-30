<?php

namespace App\Listeners\V2\AvailabilityZoneCapacity;

use App\Events\V2\AvailabilityZoneCapacity\Saved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $availabilityZoneCapacity = $event->model;

        if ($availabilityZoneCapacity->current >= $availabilityZoneCapacity->alert_critical) {

            return;
        }

        if ($availabilityZoneCapacity->current >= $availabilityZoneCapacity->alert_warning) {

            return;
        }


        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

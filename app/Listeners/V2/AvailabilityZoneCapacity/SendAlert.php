<?php

namespace App\Listeners\V2\AvailabilityZoneCapacity;

use App\Events\V2\AvailabilityZoneCapacity\Saved;
use App\Mail\AvailabilityZoneCapacityAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $availabilityZoneCapacity = $event->model;

        if ($availabilityZoneCapacity->current >= $availabilityZoneCapacity->alert_warning) {
            Mail::send(new AvailabilityZoneCapacityAlert($availabilityZoneCapacity));
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

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
        Log::info(get_class($this) . ' : Started');

        $availabilityZoneCapacity = $event->model;

        if ($availabilityZoneCapacity->current >= $availabilityZoneCapacity->alert_warning) {
            $alert = new AvailabilityZoneCapacityAlert($availabilityZoneCapacity);
            Mail::send($alert);
            Log::info(
                get_class($this) . ': ' . $availabilityZoneCapacity->type
                . ' capacity alert (' . $alert->alertLevel . ') email sent for Availability Zone ' . $availabilityZoneCapacity->availability_zone_id
            );
        }

        Log::info(get_class($this) . ' : Finished');
    }
}

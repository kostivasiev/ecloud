<?php
namespace App\Listeners\V2\AvailabilityZone\Dhcp;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Jobs\AvailabilityZone\DeleteDhcps;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new DeleteDhcps([
            'availability_zone_id' => $event->availabilityZoneId,
        ]));
    }
}
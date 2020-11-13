<?php
namespace App\Listeners\V2\AvailabilityZone\Credential;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Jobs\AvailabilityZone\DeleteCredentials;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new DeleteCredentials([
            'availability_zone_id' => $event->availabilityZoneId,
        ]));
    }
}
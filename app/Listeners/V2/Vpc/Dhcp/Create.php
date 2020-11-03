<?php

namespace App\Listeners\V2\Vpc\Dhcp;

use App\Events\V2\Vpc\Created;
use App\Models\V2\Dhcp;

class Create
{
    public function handle(Created $event)
    {
        $event->model->region->availabilityZones()->each(function ($availabilityZone) use ($event) {
            $dhcp = app()->make(Dhcp::class);
            $dhcp->vpc()->associate($event->model);
            $dhcp->availabilityZone()->associate($availabilityZone);
            $dhcp->save();
        });
    }
}

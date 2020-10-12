<?php

namespace App\Listeners\V2\Vpc;

use App\Events\V2\Vpc\Created;
use App\Models\V2\Dhcp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DhcpCreate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Created $event
     * @return void
     * @throws \Exception
     */
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

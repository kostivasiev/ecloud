<?php

namespace App\Listeners\V2;

use App\Events\V2\VpcCreated;
use App\Models\V2\Dhcp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DhcpCreate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param VpcCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(VpcCreated $event)
    {
        $event->vpc->region->availabilityZones()->each(function ($availabilityZone) use ($event) {
            $dhcp = app()->make(Dhcp::class);
            $dhcp->vpc()->associate($event->vpc);
            $dhcp->availabilityZone()->associate($availabilityZone);
            $dhcp->save();
        });
    }
}

<?php

namespace App\Listeners\V2\AvailabilityZoneCapacity;

use App\Events\V2\FloatingIp\Deleted;
use App\Models\V2\FloatingIp;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateFloatingIpCapacity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Deleted $event
     * @return void
     * @throws Exception
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $floatingIp = FloatingIp::withTrashed()->findOrFail($event->model->id);

        $floatingIp->vpc->region->availabilityZones->each(function ($availabilityZone) {
            dispatch(new \App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity($availabilityZone));
        });

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

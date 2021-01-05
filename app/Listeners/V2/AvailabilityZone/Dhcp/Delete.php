<?php
namespace App\Listeners\V2\AvailabilityZone\Dhcp;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Models\V2\AvailabilityZone;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $availabilityZone = AvailabilityZone::withTrashed()->findOrFail($event->model->getKey());
        $availabilityZone->dhcps()->each(function ($dhcp) {
            $dhcp->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

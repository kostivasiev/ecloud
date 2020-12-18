<?php
namespace App\Listeners\V2\AvailabilityZone\Credential;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Models\V2\AvailabilityZone;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $availabilityZone = AvailabilityZone::withTrashed()->findOrFail($event->model->getKey());
        $availabilityZone->credentials()->each(function ($credential) {
            $credential->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

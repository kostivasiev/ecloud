<?php
namespace App\Listeners\V2\AvailabilityZone\Credential;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Models\V2\AvailabilityZone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Delete implements ShouldQueue
{
    use InteractsWithQueue;

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

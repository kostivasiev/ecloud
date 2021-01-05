<?php

namespace App\Listeners\V2\Network;

use App\Events\V2\Network\Deleted;
use App\Models\V2\Network;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Undeploy implements ShouldQueue
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

        $network = $event->model;

        $network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
        );

        $network->setSyncCompleted();

        /**
         * Now un-deployed correctly, delete the resource
         * @see \App\Traits\V2\Syncable::delete()
         */
        Network::withoutEvents(function () use ($network) {
            $network->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

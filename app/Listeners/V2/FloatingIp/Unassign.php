<?php

namespace App\Listeners\V2\FloatingIp;

use App\Events\V2\FloatingIp\Deleted;
use App\Models\V2\FloatingIp;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Unassign implements ShouldQueue
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

        $floatingIp = FloatingIp::withTrashed()->findOrFail($event->model->getKey());

        dispatch(new \App\Jobs\FloatingIp\UnAssign([
            'floating_ip_id' => $floatingIp->getKey()
        ]));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\ComputeChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ComputeChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param ComputeChanged $event
     * @return void
     * @throws \Exception
     */
    public function handle(ComputeChanged $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $instance = $event->instance;
        Log::info('Attempting to update compute for instance ' . $instance->getKey());
        $reboot = $event->rebootRequired;

        // Handle ram_capacity
        $limit = ($instance->platform == "Windows") ? 16 : 3;
        $reboot = ((!$reboot) && (($instance->ram_capacity / 1024) <= $limit)) ? false : true;

        $instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey() . '/resize',
            [
                'json' => [
                    'numCPU' => $instance->vcpu_cores,
                    'ramMiB' => $instance->ram_capacity,
                    'guestShutdown' => $reboot
                ]
            ]
        );

        Log::info('Instance ' . $instance->getKey() . ' Compute updated. CPU: ' . $instance->vcpu_cores . ', RAM: ' . $instance->ram_capacity);

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

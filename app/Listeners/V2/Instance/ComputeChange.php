<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Updated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ComputeChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $instance = $event->model;

        if ($event->original['vcpu_cores'] == $instance->vcpu_cores && $event->original['ram_capacity'] == $instance->ram_capacity) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['event' => $event]);
            $instance->setSyncCompleted();
            return;
        }

        $reboot = false;

        $ram_limit = (($instance->platform == 'Windows') ? 16 : 3) * 1024;

        if ($instance->ram_capacity > $event->original['ram_capacity']) {
            $reboot = true;
        }

        if ($instance->ram_capacity < $ram_limit && $event->original['ram_capacity'] >= $ram_limit) {
            $reboot = true;
        }

        if ($instance->vcpu_cores > $event->original['vcpu_cores']) {
            $reboot = true;
        }

        $instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/resize',
            [
                'json' => [
                    'ramMiB' => $instance->ram_capacity,
                    'numCPU' => $instance->vcpu_cores,
                    'guestShutdown' => $reboot
                ],
            ]
        );

        $instance->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

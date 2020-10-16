<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\ComputeChanged;
use GuzzleHttp\Exception\GuzzleException;
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
        $instance = $event->instance;
        $reboot = $event->rebootRequired;
        $parameters = [];

        // Handle ram_capacity
        $parameters['ramMiB'] = $instance->ram_capacity;
        $limit = ($instance->platform == "Windows") ? 16 : 3;
        $reboot = ((!$reboot) && (($instance->ram_capacity / 1024) <= $limit)) ? false : true;

        // Handle vcpu_cores
        $parameters['numCpu'] = $instance->vcpu_cores;
        $parameters['guestShutdown'] = $reboot;

        try {
            $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey() . '/resize',
                [
                    'json' => $parameters
                ]
            );
        } catch (GuzzleException $exception) {
            $error = ($exception->hasResponse()) ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            Log::debug($error);
            $this->fail($exception);
            return;
        }
        Log::debug('Instance ' . $instance->getKey() . ' Compute updated. CPU: ' . $instance->vcpu_cores . ', RAM: ' . $instance->ram_capacity);
    }
}

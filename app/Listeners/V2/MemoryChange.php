<?php

namespace App\Listeners\V2;

use App\Events\V2\MemoryChanged;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MemoryChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  \App\Events\V2\MemoryChanged  $event
     * @return void
     * @throws \Exception
     */
    public function handle(MemoryChanged $event)
    {
        $instance = $event->instance;
        $parameters = [];

        // Handle ram_capacity
        $parameters['ramGB'] = $instance->ram_capacity;
        $limit = ($instance->platform == "Windows") ? 16 : 3;
        $reboot = (($instance->ram_capacity / 1024) <= $limit) ? false : true;

        // Handle vcpu_cores
        $parameters['numCpu'] = $instance->vcpu_cores;
        $parameters['guestShutdown'] = $reboot;

        $this->put($instance, $parameters);
    }

    public function put($instance, $parameters)
    {
        try {
            $instance->availabilityZone->nsxClient()->put(
                '/api/v2/vpc/'.$instance->vpc_id.'/instance/'.$instance->getKey().'/resize',
                $parameters
            );
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }
    }
}

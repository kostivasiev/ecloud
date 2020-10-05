<?php

namespace App\Listeners\V2;

use App\Events\V2\ComputeChanged;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ComputeChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  \App\Events\V2\ComputeChanged  $event
     * @return void
     * @throws \Exception
     */
    public function handle(ComputeChanged $event)
    {
        $instance = $event->instance;
        $reboot = $event->rebootRequired;
        $parameters = [];

        // Handle ram_capacity
        $parameters['ramGB'] = $instance->ram_capacity;
        $limit = ($instance->platform == "Windows") ? 16 : 3;
        $reboot = ((!$reboot) && (($instance->ram_capacity / 1024) <= $limit)) ? false : true;

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

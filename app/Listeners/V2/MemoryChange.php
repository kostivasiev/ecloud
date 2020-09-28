<?php

namespace App\Listeners\V2;

use App\Events\V2\MemoryChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MemoryChange implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MemoryChanged $event)
    {
        $instance = $event->instance;
        $vpcId = $instance->vpc->getKey();
        $instance->availabilityZone->nsxClient()->put(
            '/api/v2/vpc/' . $vpcId . '/instance/' . $instance->getKey() . '/resize',
            [
                'ramGB' => 0,
                'numCpu' => 0,
                'guestShutdown' => true,
            ]
        );
    }
}
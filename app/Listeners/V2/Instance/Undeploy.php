<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Deleted;
use App\Jobs\Instance\PowerOff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Undeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Deleted $event
     * @return void
     * @throws \Exception
     */
    public function handle(Deleted $event)
    {
        $instance = $event->model;

        $data = [
            'instance_id' => $instance->getKey(),
            'vpc_id' => $instance->vpc->getKey()
        ];

        dispatch((new PowerOff($data))->chain([
            new \App\Jobs\Instance\Undeploy\Undeploy($data)
        ]));
    }
}

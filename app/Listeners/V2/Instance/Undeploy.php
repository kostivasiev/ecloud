<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Deleted;
use App\Jobs\Instance\PowerOff;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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
            new Undeploy($data)
        ]));


        Log::info('Starting Undeploy for instance ' . $instance->getKey());
        $logMessage = 'Undeploy instance ' . $instance->getKey() . ': ';


    }
}

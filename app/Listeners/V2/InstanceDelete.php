<?php

namespace App\Listeners\V2;

use App\Events\V2\InstanceDeleteEvent;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InstanceDelete implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  InstanceDeleteEvent  $event
     * @return void
     * @throws \Exception
     */
    public function handle(InstanceDeleteEvent $event)
    {
        $instance = $event->instance;
        Log::info('Attempting to Delete volumes for instance '.$instance->getKey());
        $instance->volumes->each(function ($volume) {
            // If volume is only used in this instance then delete
            if ($volume->instances()->count() == 1) {
                $volume->delete();
            }
        });
        Log::info('Attempting to Delete instance '.$instance->getKey());
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone
                ->kingpinService()
                ->delete('/api/v2/vpc/'.$instance->vpc_id.'/instance/'.$instance->getKey());
            if ($response->getStatusCode() == 200) {
                Log::info('Delete finished successfully for instance '.$instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to Delete '.$instance->getKey().', Kingpin status was '.$response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to Delete '.$instance->getKey().' : '.
                $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}

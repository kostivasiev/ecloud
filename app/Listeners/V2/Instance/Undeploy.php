<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Deleted;
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
        Log::info('Attempting to Delete instance ' . $instance->getKey());
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone
                ->kingpinService()
                ->delete('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey());
            if ($response->getStatusCode() == 200) {
                Log::info('Delete finished successfully for instance ' . $instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed to Delete ' . $instance->getKey() . ', Kingpin status was ' . $response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed to Delete ' . $instance->getKey() . ' : ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}

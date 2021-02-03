<?php
namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Created;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        // Create Detached Volume
        $volume = $event->model;
        try {
            $response = $volume->availabilityZone->kingpinService()->post(
                '/api/v1/vpc/' . $volume->vpc_id . '/volume',
                [
                    'json' => [
                        'volumeId' => $volume->id,
                        'sizeGiB' => $volume->capacity,
                        'shared' => true,
                    ]
                ]
            );
        } catch (ServerException $exception) {
            // 500s are thrown on invalid parameters
            $message = 'Invalid parameter response for ' . $volume->id;
            Log::error($message, ['response' => $exception->getResponse()->getBody()]);
            $volume->setSyncFailureReason($message . PHP_EOL . $exception->getResponse()->getBody());
            $this->fail($exception);
            return;
        }

        $response = json_decode($response->getBody()->getContents());
        $volume->vmware_uuid = $response->uuid;
        $volume->save();
        $volume->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

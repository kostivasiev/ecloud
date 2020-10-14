<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Updated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class CapacityIncrease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  Updated  $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        $volume = $event->volume;
        if ($volume->capacity > $volume->getOriginal('capacity')) {
            $instance = $volume->instances()->first();
            try {
                $instance->availabilityZone->kingpinService()->put(
                    '/api/v2/vpc/'.$instance->vpc_id.'/instance/'.$instance->id.'/volume/'.$volume->vmware_uuid.'/size',
                    [
                        'sizeGiB' => $volume->capacity,
                    ]
                );
            } catch (GuzzleException $exception) {
                throw new \Exception($exception->getResponse()->getBody()->getContents());
            }
        }
    }
}

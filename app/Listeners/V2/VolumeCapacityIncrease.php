<?php

namespace App\Listeners\V2;

use App\Events\V2\VolumeCapacityUpdate;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class VolumeCapacityIncrease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param  VolumeCapacityUpdate  $event
     * @return void
     * @throws \Exception
     */
    public function handle(VolumeCapacityUpdate $event)
    {
        $volume = $event->volume;
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

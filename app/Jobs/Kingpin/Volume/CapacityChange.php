<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CapacityChange extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $volume = $this->model;

        if ($volume->instances()->count() > 0) {
            // Volume has at least one instance so needs to be resized via an instance
            $instance = $volume->instances()->first();

            $getVolumeResponse = $volume->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid);
            $getVolumeResponseJson = json_decode($getVolumeResponse->getBody()->getContents());
            if ($getVolumeResponseJson->sizeGiB == $volume->capacity) {
                Log::debug('Volume capacity already set to expected size on instance ' . $instance->id . ', skipping');
                return;
            }

            $volume->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size',
                [
                    'json' => [
                        'sizeGiB' => $volume->capacity,
                    ],
                ]
            );
        } else {
            // Volume has no instances, increase directly
            $getVolumeResponse = $volume->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $volume->vpc_id . '/volume/' . $volume->vmware_uuid);
            $getVolumeResponseJson = json_decode($getVolumeResponse->getBody()->getContents());
            if ($getVolumeResponseJson->sizeGiB == $volume->capacity) {
                Log::debug('Volume capacity already set to expected size, skipping');
                return;
            }

            $volume->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $volume->vpc_id . '/volume/' . $volume->vmware_uuid . '/size',
                [
                    'json' => [
                        'sizeGiB' => $volume->capacity,
                    ],
                ]
            );
        }

        Log::debug('Volume ' . $volume->id . ' capacity increased to ' . $volume->capacity);
    }
}

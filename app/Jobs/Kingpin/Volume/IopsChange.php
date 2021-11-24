<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class IopsChange extends Job
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
        if (!$volume->instances()->count()) {
            Log::info('Volume not attached to any instances, skipping');
            return;
        }

        if ($volume->is_shared) {
            Log::info(get_class($this) . ' : Shared volume detected, skipping');
            return;
        }

        foreach ($volume->instances as $instance) {
            $getVolumeResponse = $volume->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid);
            $getVolumeResponseJson = json_decode($getVolumeResponse->getBody()->getContents());
            if ($getVolumeResponseJson->iops == $volume->iops) {
                Log::debug('Volume IOPS already set to expected limit on instance ' . $instance->id . ', skipping');
                continue;
            }

            $volume->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/iops',
                [
                    'json' => [
                        'limit' => $volume->iops,
                    ],
                ]
            );

            Log::debug('Volume ' . $volume->id . ' iops changed to ' . $volume->iops . ' on instance ' . $instance->id);
        }
    }
}

<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class PrepareOsDisk extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);

        $getInstanceResponse = $instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $instance->id . ', could not decode response');
        }

        if (count($instanceData->volumes) > 1) {
            throw new \Exception('Deploy failed for ' . $instance->id . ', Multi volume instance deploy detected. Multiple volumes are not currently supported.');
        }

        // Create Volumes from kingpin
        foreach ($instanceData->volumes as $volumeData) {
            $volume = app()->make(Volume::class);
            $volume->vpc()->associate($instance->vpc);
            $volume->availability_zone_id = $instance->availability_zone_id;
            $volume->capacity = $this->data['volume_capacity'];
            $volume->vmware_uuid = $volumeData->uuid;
            $volume->save();
            $volume->instances()->attach($instance);

            Log::info(get_class($this) . ' : Created volume resource ' . $volume->getKey() . ' for volume ' . $volume->vmware_uuid);

            // Send created Volume ID's to Kinpin
            $instance->availabilityZone->kingpinService()->put(
                '/api/v1/vpc/' . $this->data['vpc_id'] . '/volume/' . $volume->vmware_uuid . '/resourceid',
                [
                    'json' => [
                        'volumeId' => $volume->getKey()
                    ]
                ]
            );

            Log::info(get_class($this) . ' : Volume ' . $volume->vmware_uuid . ' successfully updated with resource ID ' . $volume->getKey());
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}

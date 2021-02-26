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
            $volume->iops = $this->data['volume_iops'];
            $volume->vmware_uuid = $volumeData->uuid;
            $volume->save();

            // This is an ugly hack since attach will fail as the save marks the volume as out of sync and
            // the background job hasn't had chance to run yet meaning attach will fail.
            while ($volume->getStatus() === 'in-progress') {
                Log::debug('Waiting for Volume ' . $volume->id . ' to finish syncing...');
                sleep(5);
            }

            // Now the save has completed, attach it to the instance
            $volume->instances()->attach($instance);

            Log::info(get_class($this) . ' : Created volume resource ' . $volume->id . ' for volume ' . $volume->vmware_uuid);

            // Send created Volume ID's to Kinpin
            $instance->availabilityZone->kingpinService()->put(
                '/api/v1/vpc/' . $this->data['vpc_id'] . '/volume/' . $volume->vmware_uuid . '/resourceid',
                [
                    'json' => [
                        'volumeId' => $volume->id
                    ]
                ]
            );

            // Update the volume iops
            $volume->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->data['vpc_id'] . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/iops',
                [
                    'json' => [
                        'limit' => $volume->iops
                    ]
                ]
            );

            Log::info(get_class($this) . ' : Volume ' . $volume->vmware_uuid . ' successfully updated with resource ID ' . $volume->id);
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}

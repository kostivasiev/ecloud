<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PrepareOsDisk extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $getInstanceResponse = $this->instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $this->instance->id . ', could not decode response');
        }

        if (count($instanceData->volumes) > 1) {
            throw new \Exception('Deploy failed for ' . $this->instance->id . ', Multi volume instance deploy detected. Multiple volumes are not currently supported.');
        }

        // Create Volumes from kingpin
        foreach ($instanceData->volumes as $volumeData) {
            $volume = app()->make(Volume::class);
            $volume->vpc()->associate($this->instance->vpc);
            $volume->name = $this->instance->id . ' - ' . $this->instance->image->name;
            $volume->availability_zone_id = $this->instance->availability_zone_id;
            $volume->capacity = $this->instance->deploy_data['volume_capacity'];
            $volume->iops = $this->instance->deploy_data['volume_iops'];
            $volume->vmware_uuid = $volumeData->uuid;
            $volume->save();

            Log::info(get_class($this) . ' : Created volume resource ' . $volume->id . ' for volume ' . $volume->vmware_uuid);

            // Send created Volume ID's to Kinpin
            $this->instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/volume/' . $volume->vmware_uuid . '/resourceid',
                [
                    'json' => [
                        'volumeId' => $volume->id
                    ]
                ]
            );

            // Update the volume iops
            $volume->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/volume/' . $volume->vmware_uuid . '/iops',
                [
                    'json' => [
                        'limit' => $volume->iops
                    ]
                ]
            );

            Log::info(get_class($this) . ' : Volume ' . $volume->vmware_uuid . ' successfully updated with resource ID ' . $volume->id);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}

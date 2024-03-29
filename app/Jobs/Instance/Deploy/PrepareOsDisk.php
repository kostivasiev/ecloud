<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PrepareOsDisk extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $getInstanceResponse = $this->model->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
        );

        $instanceData = json_decode($getInstanceResponse->getBody()->getContents());
        if (!$instanceData) {
            throw new \Exception('Deploy failed for ' . $this->model->id . ', could not decode response');
        }

        if (count($instanceData->volumes) > 1) {
            throw new \Exception('Deploy failed for ' . $this->model->id . ', Multi volume instance deploy detected. Multiple volumes are not currently supported.');
        }

        // Create Volumes from kingpin
        foreach ($instanceData->volumes as $volumeData) {
            $volume = app()->make(Volume::class);
            $volume->vpc()->associate($this->model->vpc);
            $volume->name = $this->model->id . ' - ' . $this->model->image->name;
            $volume->availability_zone_id = $this->model->availability_zone_id;
            $volume->capacity = $this->model->deploy_data['volume_capacity'];
            $volume->iops = $this->model->deploy_data['volume_iops'];
            $volume->vmware_uuid = $volumeData->uuid;
            $volume->os_volume = true;
            $volume->save();
            $volume->instances()->attach($this->model);

            Log::info(get_class($this) . ' : Created volume resource ' . $volume->id . ' for volume ' . $volume->vmware_uuid);

            // Send created Volume ID's to Kinpin
            $this->model->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->model->vpc->id . '/volume/' . $volume->vmware_uuid . '/resourceid',
                [
                    'json' => [
                        'volumeId' => $volume->id
                    ]
                ]
            );

            // Trigger sync to set IOPS/Capacity
            $volume->syncSave();

            Log::info(get_class($this) . ' : Volume ' . $volume->vmware_uuid . ' successfully updated with resource ID ' . $volume->id);
        }
    }
}

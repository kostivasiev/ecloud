<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class AttachOsDisk extends Job
{
    const RETRY_DELAY = 1;

    public $tries = 60;

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

        foreach ($instanceData->volumes as $volumeData) {
            $volume = Volume::find($volumeData->volumeId);

            if ($volume->getStatus() === Sync::STATUS_INPROGRESS) {
                Log::debug('Waiting for Volume ' . $volume->id . ' to finish syncing...');
                $this->release(static::RETRY_DELAY);
                return;
            }

            // Now the save from PrepareOsDisk has completed, attach it to the instance
            $volume->instances()->attach($instance);

            Log::info(get_class($this) . ' : Volume ' . $volume->id . ' successfully attached');
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}

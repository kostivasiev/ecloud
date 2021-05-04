<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Models\V2\Volume;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AttachOsDisk extends Job
{
    use Batchable;

    const RETRY_DELAY = 1;

    public $tries = 60;

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

        foreach ($instanceData->volumes as $volumeData) {
            $volume = Volume::find($volumeData->volumeId);

            if ($volume->sync->status === Sync::STATUS_INPROGRESS) {
                Log::info('Waiting for Volume ' . $volume->id . ' to finish syncing...');
                $this->release(static::RETRY_DELAY);
                return;
            }

            // Now the save from PrepareOsDisk has completed, attach it to the instance
            Model::withoutEvents(function () use ($volume) {
                $volume->instances()->attach($this->instance);
            });

            Log::info(get_class($this) . ' : Volume ' . $volume->id . ' successfully attached');
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}

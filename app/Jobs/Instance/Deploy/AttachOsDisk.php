<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AttachOsDisk extends Job
{
    use Batchable, JobModel;

    const RETRY_DELAY = 1;

    public $tries = 60;

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

        foreach ($instanceData->volumes as $volumeData) {
            $volume = Volume::find($volumeData->volumeId);

            if ($volume->sync->status === Sync::STATUS_INPROGRESS) {
                Log::info('Waiting for Volume ' . $volume->id . ' to finish syncing...');
                $this->release(static::RETRY_DELAY);
                return;
            }

            // Now the save from PrepareOsDisk has completed, attach it to the instance
            $volume->instances()->attach($this->model);

            Log::info(get_class($this) . ' : Volume ' . $volume->id . ' successfully attached');
        }
    }
}

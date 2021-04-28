<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use SyncableBatch;

    private $sync;
    private $originalValues;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
        $this->originalValues = $sync->resource->getOriginal();
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

        $volume = $this->sync->resource;

        $jobs = [
            new Deploy($volume),
        ];

        // DO NOT DO THIS! Original values will be removed in the future!
        if (isset($this->originalValues['iops']) && $this->originalValues['iops'] != $volume->iops) {
            $jobs[] = new IopsChange($volume);
        }

        // DO NOT DO THIS! Original values will be removed in the future!
        if (isset($this->originalValues['capacity']) && $this->originalValues['capacity'] != $volume->capacity) {
            $jobs[] = new CapacityChange($volume);
        }

        $this->updateSyncBatch([
            $jobs
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use SyncableBatch;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

        $this->deleteSyncBatch([
            [
                new DeleteChildResources($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\Undeploy($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\UndeployCheck($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Undeploy($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\UndeployCheck($this->sync->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

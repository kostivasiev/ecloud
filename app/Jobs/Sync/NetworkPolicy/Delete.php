<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Delete extends Job
{
    use SyncableBatch, JobModel;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        $this->deleteSyncBatch([
            [
                new DeleteChildResources($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\Undeploy($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\UndeployCheck($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Undeploy($this->sync->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\UndeployCheck($this->sync->resource),
            ]
        ])->dispatch();
    }
}

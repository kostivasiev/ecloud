<?php

namespace App\Jobs\Sync\Nic;

use App\Jobs\Job;
use App\Jobs\Nsx\Nic\CreateDHCPLease;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Update extends Job
{
    use SyncableBatch, JobModel;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        $this->updateSyncBatch([
            [
                new CreateDHCPLease($this->sync->resource),
            ]
        ])->dispatch();
    }
}

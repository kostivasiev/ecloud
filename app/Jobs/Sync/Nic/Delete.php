<?php

namespace App\Jobs\Sync\Nic;

use App\Jobs\Job;
use App\Jobs\Nic\UnassignFloatingIP;
use App\Jobs\Nsx\Nic\RemoveDHCPLease;
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
                new RemoveDHCPLease($this->sync->resource),
                new UnassignFloatingIP($this->sync->resource),
            ]
        ])->dispatch();
    }
}

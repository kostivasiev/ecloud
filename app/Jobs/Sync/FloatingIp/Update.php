<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Job;
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
        // Here we chain AllocateIp and AllocateIpCheck
        $this->updateSyncBatch([
            [
                new AllocateIp($this->sync->resource),
                new AwaitNatSync($this->sync->resource),
            ]
        ])->dispatch();
    }
}

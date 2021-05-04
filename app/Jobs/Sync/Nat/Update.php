<?php

namespace App\Jobs\Sync\Nat;

use App\Jobs\Job;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Jobs\Nat\Deploy;
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
                new AwaitIPAddressAllocation($this->sync->resource),
                new Deploy($this->sync->resource),
            ]
        ])->dispatch();
    }
}

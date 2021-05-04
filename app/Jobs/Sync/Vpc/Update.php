<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Job;
use App\Jobs\Vpc\AwaitDhcpSync;
use App\Jobs\Vpc\CreateDhcps;
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
                new CreateDhcps($this->sync->resource),
                new AwaitDhcpSync($this->sync->resource),
            ],
        ])->dispatch();
    }
}

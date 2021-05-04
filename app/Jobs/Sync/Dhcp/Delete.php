<?php

namespace App\Jobs\Sync\Dhcp;

use App\Jobs\Job;
use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
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
                new Undeploy($this->sync->resource),
                new UndeployCheck($this->sync->resource),
            ]
        ])->dispatch();
    }
}

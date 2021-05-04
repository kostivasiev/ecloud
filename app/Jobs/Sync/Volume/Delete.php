<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Delete extends Job
{
    use SyncableBatch, JobModel;

    /** @var Sync */
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
            ]
        ])->dispatch();
    }
}

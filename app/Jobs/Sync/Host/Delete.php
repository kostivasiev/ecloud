<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckExists;
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
        $host = $this->sync->resource;

        $this->deleteSyncBatch([
            new \App\Jobs\Kingpin\Host\MaintenanceMode($host),
            new \App\Jobs\Kingpin\Host\DeleteInVmware($host),
            new \App\Jobs\Conjurer\Host\PowerOff($host),
            new \App\Jobs\Artisan\Host\RemoveFrom3Par($host),
            new \App\Jobs\Conjurer\Host\DeleteServiceProfile($host),
        ])->dispatch();
    }
}

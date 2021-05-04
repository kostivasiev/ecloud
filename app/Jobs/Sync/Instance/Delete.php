<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\Undeploy;
use App\Jobs\Job;
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
                new PowerOff($this->sync->resource),
                new Undeploy($this->sync->resource),
                new DeleteVolumes($this->sync->resource),
                new DeleteNics($this->sync->resource),
                new AwaitNicRemoval($this->sync->resource),
            ],
        ])->dispatch();
    }
}

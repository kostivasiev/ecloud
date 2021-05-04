<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Router\Deploy;
use App\Jobs\Router\DeployRouterDefaultRule;
use App\Jobs\Router\DeployRouterLocale;
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
                new Deploy($this->sync->resource),
                new DeployRouterLocale($this->sync->resource),
                new DeployRouterDefaultRule($this->sync->resource),
            ],
        ])->dispatch();
    }
}

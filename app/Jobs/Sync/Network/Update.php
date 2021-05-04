<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitRouterSync;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Jobs\Network\DeploySecurityProfile;
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
                new AwaitRouterSync($this->sync->resource),
                new Deploy($this->sync->resource),
                new DeploySecurityProfile($this->sync->resource),
                new DeployDiscoveryProfile($this->sync->resource),
            ],
        ])->dispatch();
    }
}

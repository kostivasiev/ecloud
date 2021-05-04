<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitPortRemoval;
use App\Jobs\Network\Undeploy;
use App\Jobs\Network\UndeployCheck;
use App\Jobs\Network\UndeployDiscoveryProfiles;
use App\Jobs\Network\UndeployQoSProfiles;
use App\Jobs\Network\UndeploySecurityProfiles;
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
                new AwaitPortRemoval($this->sync->resource),
                new UndeploySecurityProfiles($this->sync->resource),
                new UndeployDiscoveryProfiles($this->sync->resource),
                new UndeployQoSProfiles($this->sync->resource),
                new Undeploy($this->sync->resource),
                new UndeployCheck($this->sync->resource),
            ],
        ])->dispatch();
    }
}

<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\DeployCheck;
use App\Jobs\Nsx\NetworkPolicy\Deploy as DeployNetworkPolicy;
use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy as DeploySecurityGroup;
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
                new DeploySecurityGroup($this->sync->resource),
                new DeployCheck(
                    $this->sync->resource,
                    $this->sync->resource->network->router->availabilityZone,
                    '/infra/domains/default/groups/'
                ),
                new DeployNetworkPolicy($this->sync->resource),
                new DeployCheck(
                    $this->sync->resource,
                    $this->sync->resource->network->router->availabilityZone,
                    '/infra/domains/default/security-policies/'
                )
            ]
        ])->dispatch();
    }
}

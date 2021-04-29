<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy as DeploySecurityGroup;
use App\Jobs\Nsx\NetworkPolicy\Deploy as DeployNetworkPolicy;
use App\Jobs\Nsx\DeployCheck;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use SyncableBatch;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

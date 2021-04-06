<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitRouterSync;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Jobs\Network\DeploySecurityProfile;
use App\Jobs\Network\Deploy;
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
                new AwaitRouterSync($this->sync->resource),
                new Deploy($this->sync->resource),
                new DeploySecurityProfile($this->sync->resource),
                new DeployDiscoveryProfile($this->sync->resource),
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

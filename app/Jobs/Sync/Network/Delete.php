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
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

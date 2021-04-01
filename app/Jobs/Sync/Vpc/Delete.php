<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\Undeploy as InstanceUndeploy;
use App\Jobs\Job;
use App\Jobs\Vpc\AwaitDhcpRemoval;
use App\Jobs\Vpc\DeleteDhcps;
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
                new DeleteDhcps($this->sync->resource),
                new AwaitDhcpRemoval($this->sync->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

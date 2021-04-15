<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AllocateIp;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Job;
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

        // Here we chain AllocateIp and AllocateIpCheck
        $this->updateSyncBatch([
            [
                new AllocateIp($this->sync->resource),
                new AwaitNatSync($this->sync->resource),
            ]
        ])->dispatch();


        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

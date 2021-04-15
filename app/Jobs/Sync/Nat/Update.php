<?php

namespace App\Jobs\Sync\Nat;

use App\Jobs\FloatingIp\AllocateIp;
use App\Jobs\Job;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Jobs\Nat\Deploy;
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
                new AwaitIPAddressAllocation($this->sync->resource),
                new Deploy($this->sync->resource),
            ]
        ])->dispatch();


        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

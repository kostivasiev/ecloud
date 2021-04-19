<?php

namespace App\Jobs\Sync\Nat;

use App\Jobs\Job;
use App\Jobs\Nat\Undeploy;
use App\Jobs\Nat\UndeployCheck;
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
                new Undeploy($this->sync->resource),
                new UndeployCheck($this->sync->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

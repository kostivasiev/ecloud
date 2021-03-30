<?php

namespace App\Jobs\Sync\Vpc;

use App\Jobs\Job;
use App\Jobs\Vpc\AwaitDHCPSync;
use App\Jobs\Vpc\CreateDHCPs;
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
                    new CreateDHCPs($this->sync->resource),
                    new AwaitDHCPSync($this->sync->resource),
                ],
            ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

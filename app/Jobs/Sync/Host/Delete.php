<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
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

        $host = $this->sync->resource;

        // TODO
//        $this->deleteSyncBatch([
//        ])->dispatch();

        // TODO delete this when we implement deleteSyncBatch
        $this->sync->completed = true;
        $this->sync->save();
        $this->sync->resource->delete();

        Log::info(get_class($this) . ' : Finished', ['id' => $host->id]);
    }
}

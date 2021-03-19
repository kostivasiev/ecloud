<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Jobs\Kingpin\Volume\UndeployCheck;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use SyncableBatch;

    /** @var Volume */
    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id]);

        $this->deleteSyncBatch([
            [
                new Undeploy($this->sync->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id]);
    }
}

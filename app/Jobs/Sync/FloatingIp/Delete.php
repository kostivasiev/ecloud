<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\DeleteNats;
use App\Jobs\Job;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;
use Illuminate\Bus\Batch;

class Delete extends Job
{
    use SyncableBatch, JobModel;

    private Sync $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        $floatingIp = $this->sync->resource;

        $this->deleteSyncBatch(
            [
                new DeleteNats($floatingIp),
                new AwaitNatRemoval($floatingIp),
            ]
        )
            // TODO: Remove this once atomic db constraint removed
        ->then(function (Batch $batch) use ($floatingIp) {
            $floatingIp->deleted = time();
            $floatingIp->saveQuietly();
        })->dispatch();
    }
}

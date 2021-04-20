<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
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
        Log::info(
            get_class($this) . ' : Started',
            [
                'id' => $this->sync->id,
                'resource_id' => $this->sync->resource->id
            ]
        );

        $hostGroup = $this->sync->resource;

        $this->deleteSyncBatch([
                new DeleteTransportNodeProfile($hostGroup),
                new DeleteCluster($hostGroup),
        ])->dispatch();

        Log::info(
            get_class($this) . ' : Finished',
            [
                'id' => $this->sync->id,
                'resource_id' => $this->sync->resource->id
            ]
        );
    }
}

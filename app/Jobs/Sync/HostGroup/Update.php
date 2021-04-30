<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNode;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
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

        $hostGroup = $this->sync->resource;

        $this->updateSyncBatch([
            [
                new CreateCluster($hostGroup),
                new CreateTransportNode($hostGroup),
                new PrepareCluster($hostGroup)
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}

<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNode;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Update extends Job
{
    use SyncableBatch, JobModel;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        $hostGroup = $this->sync->resource;

        $this->updateSyncBatch([
            [
                new CreateCluster($hostGroup),
                new CreateTransportNode($hostGroup),
                new PrepareCluster($hostGroup)
            ],
        ])->dispatch();
    }
}

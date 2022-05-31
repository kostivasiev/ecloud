<?php

namespace App\Jobs\FloatingIpResource;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteSourceNat extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $floatingIpResource = $this->task->resource;
        $floatingIp = $floatingIpResource->floatingIp;

        if (!$floatingIp->sourceNat()->exists()) {
            return;
        }

        $this->deleteSyncableResource($floatingIp->sourceNat->id);
    }
}

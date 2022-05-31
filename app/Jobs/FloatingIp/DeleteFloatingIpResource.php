<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteFloatingIpResource extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $floatingIp = $this->task->resource;

        if ($floatingIp->floatingIpResource()->exists()) {
            $this->deleteSyncableResource($floatingIp->floatingIpResource->id);
        }
    }
}

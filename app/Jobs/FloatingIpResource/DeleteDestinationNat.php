<?php

namespace App\Jobs\FloatingIpResource;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteDestinationNat extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $floatingIpResource = $this->task->resource;
        $floatingIp = $floatingIpResource->floatingIp;

        if ($floatingIp->destinationNat()->exists()) {
            $this->info('Deleting DNAT ' . $floatingIp->destinationNat->id . ' from unassigned floating IP');
        }

        if (!$floatingIp->destinationNat()->exists()) {
            return;
        }

        $this->deleteSyncableResource($floatingIp->destinationNat->id);
    }
}

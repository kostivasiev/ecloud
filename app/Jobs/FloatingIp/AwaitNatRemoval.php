<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class AwaitNatRemoval extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private FloatingIp $model;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    /**
     * Await the deletion of any NATs that were created as part of assigning the floating IP to a NIC.
     */
    public function handle()
    {
        $floatingIp = $this->model;

        if ($floatingIp->sourceNat()->exists()) {
            $this->awaitSyncableResources([$floatingIp->sourceNat->id]);
        }

        if ($floatingIp->destinationNat()->exists()) {
            $this->awaitSyncableResources([$floatingIp->destinationNat->id]);
        }
    }
}

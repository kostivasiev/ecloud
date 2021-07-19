<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveUnassignedNicNats extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    /**
     * Check if the floating IP was unassigned from a NIC and delete any NATs that were created.
     */
    public function handle()
    {
        $floatingIp = $this->model;

        if ($floatingIp->sourceNat()->exists()) {
            if (empty($floatingIp->resource_id) || $floatingIp->sourceNat->source->id != $floatingIp->resource_id) {
                Log::info(get_class($this) . ' : Deleting SNAT ' . $floatingIp->sourceNat->id . ' from unassigned floating IP');
                $floatingIp->sourceNat->syncDelete();
            }
        }

        if ($floatingIp->destinationNat()->exists()) {
            if (empty($floatingIp->resource_id) || $floatingIp->destinationNat->translated->id != $floatingIp->resource_id) {
                Log::info(get_class($this) . ' : Deleting SNAT ' . $floatingIp->sourceNat->id . ' from unassigned floating IP');
                $floatingIp->sourceNat->syncDelete();
            }
        }
    }
}

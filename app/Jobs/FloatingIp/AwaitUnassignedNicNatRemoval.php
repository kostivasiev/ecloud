<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitUnassignedNicNatRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

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

        if (!$floatingIp->resource_id) {
            if ($this->model->sourceNat()->exists() && $this->model->sourceNat->sync->status == Sync::STATUS_FAILED) {
                Log::error('Source NAT in failed sync state, abort', ['id' => $this->model->id, 'nat_id' => $this->model->sourceNat->id]);
                $this->fail(new \Exception("Source NAT '" . $this->model->sourceNat->id . "' in failed sync state"));
                return;
            }

            if ($this->model->destinationNat()->exists() && $this->model->destinationNat->sync->status == Sync::STATUS_FAILED) {
                Log::error('Destination NAT in failed sync state, abort', ['id' => $this->model->id, 'nat_id' => $this->model->destinationNat->id]);
                $this->fail(new \Exception("Destination NAT '" . $this->model->destinationNat->id . "' in failed sync state"));
                return;
            }

            if ($this->model->sourceNat()->exists() || $this->model->destinationNat()->exists()) {
                Log::warning('NAT(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
                return $this->release($this->backoff);
            }
        }
    }
}

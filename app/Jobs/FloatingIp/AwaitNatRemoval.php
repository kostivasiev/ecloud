<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Router;
use App\Support\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNatRemoval extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private FloatingIp $floatingIp;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->floatingIp = $floatingIp;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->floatingIp->id]);

        if ($this->floatingIp->sourceNat()->exists() && $this->floatingIp->sourceNat->sync->status == Sync::STATUS_FAILED) {
            Log::error('Source NAT in failed sync state, abort', ['id' => $this->floatingIp->id, 'nat_id' => $this->floatingIp->sourceNat->id]);
            $this->fail(new \Exception("Source NAT '" . $this->floatingIp->sourceNat->id . "' in failed sync state"));
            return;
        }

        if ($this->floatingIp->destinationNat()->exists() != null && $this->floatingIp->destinationNat->sync->status == Sync::STATUS_FAILED) {
            Log::error('Destination NAT in failed sync state, abort', ['id' => $this->floatingIp->id, 'nat_id' => $this->floatingIp->destinationNat->id]);
            $this->fail(new \Exception("Destination NAT '" . $this->floatingIp->destinationNat->id . "' in failed sync state"));
            return;
        }

        if ($this->floatingIp->sourceNat()->exists() || $this->floatingIp->destinationNat()->exists()) {
            Log::warning('NAT(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->floatingIp->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->floatingIp->id]);
    }
}

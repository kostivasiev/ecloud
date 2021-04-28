<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

// TODO: NAT state should be exposed seperately, rather than this being part of floating IP update sync
class AwaitNatSync extends Job
{
    use Batchable;

    public $tries = 60;
    public $backoff = 10;

    private $floatingIp;

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

        if ($this->floatingIp->sourceNat()->exists() && $this->floatingIp->sourceNat->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Source NAT not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->floatingIp->id, 'nat_id' => $this->floatingIp->sourceNat->id]);
            return $this->release($this->backoff);
        }

        if ($this->floatingIp->destinationNat()->exists() && $this->floatingIp->destinationNat->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Destination NAT not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->floatingIp->id, 'nat_id' => $this->floatingIp->destinationNat->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->floatingIp->id]);
    }
}

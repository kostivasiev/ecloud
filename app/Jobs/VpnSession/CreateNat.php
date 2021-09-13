<?php

namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateNat extends Job
{
    use Batchable, LoggableModelJob;

    private VpnSession $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    /**
     * Check if the floating IP was assigned to a NIC and create NATs
     */
    public function handle()
    {
        $vpnSession = $this->model;

        if (!$this->model->noSNATRule()->exists()) {
            $nat = app()->make(Nat::class);
            $nat->destination()->associate($floatingIp);
            $nat->translated()->associate($this->resource);
            $nat->action = Nat::ACTION_NOSNAT;
            $nat->sequence = config('defaults.floating-ip.nat.sequence');
            $task = $nat->syncSave();
            Log::info(get_class($this) . ' : Creating DNAT for floating IP ' . $floatingIp->id, ['task_id' => $task->id]);
        }
    }
}

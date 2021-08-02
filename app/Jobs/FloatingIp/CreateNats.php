<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateNats extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    private $resource;

    public function __construct(FloatingIp $floatingIp, $resource)
    {
        $this->model = $floatingIp;
        $this->resource = $resource;
    }

    /**
     * Check if the floating IP was assigned to a NIC and create NATs
     */
    public function handle()
    {
        $floatingIp = $this->model;

        if (!($this->resource instanceof Nic)) {
            Log::info(get_class($this) . ' : Resource is not a NIC, skipping');
            return;
        }

        if (!$floatingIp->destinationNat()->exists()) {
            $nat = app()->make(Nat::class);
            $nat->destination()->associate($floatingIp);
            $nat->translated()->associate($this->resource);
            $nat->action = Nat::ACTION_DNAT;
            $task = $nat->syncSave();
            Log::info(get_class($this) . ' : Creating DNAT for floating IP ' . $floatingIp->id, ['task_id' => $task->id]);
        }

        if (!$floatingIp->sourceNat()->exists()) {
            $nat = app()->make(Nat::class);
            $nat->source()->associate($this->resource);
            $nat->translated()->associate($floatingIp);
            $nat->action = NAT::ACTION_SNAT;
            $task = $nat->syncSave();
            Log::info(get_class($this) . ' : Creating SNAT for floating IP ' . $floatingIp->id, ['task_id' => $task->id]);
        }
    }
}

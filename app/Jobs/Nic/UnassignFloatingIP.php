<?php

namespace App\Jobs\Nic;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable;
    
    private $nic;

    public function __construct(Nic $nic)
    {
        $this->nic = $nic;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->nic->id]);

        $nic = $this->nic;
        $logMessage = 'UnassignFloatingIp for NIC ' . $nic->id . ': ';
        Log::info($logMessage . 'Started');

        if ($this->nic->sourceNat()->exists()) {
            Log::info($logMessage . 'Floating IP ' . $this->nic->sourceNat->translated_id . ' unassigned');
            $this->nic->sourceNat->delete();
        }
        if ($this->nic->destinationNat()->exists()) {
            Log::info($logMessage . 'Floating IP ' . $this->nic->sourceNat->translated_id . ' unassigned');
            $this->nic->destinationNat->delete();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nic->id]);
    }
}

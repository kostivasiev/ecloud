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
        Nat::whereHasMorph('translated', [Nic::class, FloatingIp::class], function (Builder $query) use ($nic) {
            $query->where('translated_id', $nic->id)->withTrashed();
        })
            ->orWhereHasMorph('source', [Nic::class, FloatingIp::class], function (Builder $query) use ($nic) {
                $query->where('source_id', $nic->id)->withTrashed();
            })
            ->each(function ($nat) use ($logMessage) {
                Log::info($logMessage . 'Floating IP ' . $nat->destination_id . ' unassigned');
                $nat->delete();
            });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nic->id]);
    }
}

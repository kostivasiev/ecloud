<?php

namespace App\Listeners\V2\Nic;

use App\Events\V2\Nic\Deleted;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $nic = $event->model;
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

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

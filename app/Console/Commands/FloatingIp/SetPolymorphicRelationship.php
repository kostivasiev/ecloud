<?php

namespace App\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetPolymorphicRelationship extends Command
{
    protected $signature = 'floating-ip:set-polymorphic-relationship';

    protected $description = 'Updates existing floating IP\'s that are assigned to a resource and sets the polymorphic relationship';

    public function handle()
    {
        Nat::where('action', 'DNAT')->each(function ($nat) {
            try {
                $this->info('Setting polymorphic relationship for ' . $nat->destination_id);

                if ($nat->destination instanceof FloatingIp) {
                    $fip = $nat->destination;
                    $fip->resource()->associate($nat->translated);
                    $fip->save();
                }
            } catch (\Throwable $exception) {
                Log::error('Failed setting polymorphic relationship for ' . $nat->destination_id, [
                    'router_id' => $nat->destination_id,
                    'exception' => $exception,
                ]);
            }
        });

        return Command::SUCCESS;
    }
}

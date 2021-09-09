<?php


namespace App\Console\Commands\Fip;

use App\Models\V2\FloatingIp;
use App\Models\V2\Host;
use App\Models\V2\Nat;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;

/**
 * Class PopulateFipNatSequence
 * Populates FIP SNAT/DNAT model sequence
 * @package App\Console\Commands\Nat
 */
class PopulateFipNatSequence extends Command
{
    protected $signature = 'fip:populatenatsequence';
    protected $description = 'Populates FIP NAT sequence number for issue #1142';

    public function handle()
    {
        $fips = FloatingIp::all();
        foreach ($fips as $fip) {
            if ($fip->sourceNat()->exists()) {
                $this->info('Populating sequence for SNAT ' . $fip->sourceNat->id . ' for floating IP ' . $fip->id);
                $fip->sourceNat->sequence = config('defaults.floating-ip.nat.sequence');
                $fip->sourceNat->save();
            }

            if ($fip->destinationNat()->exists()) {
                $this->info('Populating sequence for DNAT ' . $fip->sourceNat->id . ' for floating IP ' . $fip->id);
                $fip->destinationNat->sequence = config('defaults.floating-ip.nat.sequence');
                $fip->destinationNat->save();
            }
        }

        return Command::SUCCESS;
    }
}

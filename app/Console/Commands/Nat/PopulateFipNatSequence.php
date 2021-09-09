<?php


namespace App\Console\Commands\Nat;

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
    protected $signature = 'nat:populatefipnatsequence';
    protected $description = 'Populates FIP NAT sequence number for issue #1142';

    public function handle()
    {
        $nats = Nat::all();
        foreach ($nats as $nat) {
            if ($nat->action == Nat::ACTION_DNAT || $nat->action == Nat::ACTION_SNAT) {
                $this->info('Populating sequence for NAT ' . $nat->id);
                $nat->sequence = config('defaults.floating-ip.nat.sequence');
                $nat->save();
            }
        }

        return Command::SUCCESS;
    }
}

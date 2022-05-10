<?php

namespace App\Console\Commands\IpAddress;

use App\Models\V2\IpAddress;
use App\Console\Commands\Command;

/**
 * Class PopulateNetworkId
 * Populate network_id column on ip_addresses
 * @package App\Console\Commands\Kingpin\Instance
 */
class PopulateNetworkId extends Command
{
    protected $signature = 'ip-addresses:populate-network-id';
    protected $description = 'Populate network_id column on ip_addresses';

    public function handle()
    {
        IpAddress::with('nics')->each(function ($ipAddress) {
            if ($ipAddress->nics->count() > 0) {
                $ipAddress->network_id = $ipAddress->nics->first()->network_id;
                $ipAddress->save();
                $this->line('network_id set for IP address ' . $ipAddress->id);
            }
        });

        return Command::SUCCESS;
    }
}

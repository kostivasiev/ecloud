<?php
namespace App\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Models\V2\IpAddress;
use Illuminate\Console\Command;

class MigrateFips extends Command
{

    protected $signature = 'floating-ip:migrate-fips {--T|test-run}';
    protected $description = 'Migrate floating ips from nic assignment to ip assignment';

    public function handle()
    {
        FloatingIp::where('resource_type', '=', 'nic')
            ->each(function ($floatingIp) {
                $this->info('Processing floating ip ' . $floatingIp->id);
                $nic = $floatingIp->resource;
                $networkId = $nic->network_id;
                $ipAddress = IpAddress::where('network_id', $networkId)->first();
                if (!$ipAddress) {
                    $ipAddress = app()->make(IpAddress::class);
                    $ipAddress->fill([
                        'network_id' => $networkId,
                    ]);
                    $ipAddress->save();
                }
                $ipAddress->ip_address = $nic->ip_address;
                if (!$this->option('test-run')) {
                    $ipAddress->save();
                    $nic->setAttribute('ip_address', null)->saveQuietly();
                    $floatingIp->resource()->associate($ipAddress);
                    $floatingIp->save();
                    $this->info('Floating Ip ' . $floatingIp->id . ' updated.');
                }
            });
    }
}

<?php
namespace App\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\IpAddress;
use App\Console\Commands\Command;

class MigrateFips extends Command
{

    protected $signature = 'floating-ip:migrate-fips {--T|test-run}';
    protected $description = 'Migrate floating ips from nic assignment to ip assignment';

    public function handle()
    {
        FloatingIpResource::where('resource_type', '=', 'nic')
            ->each(function ($floatingIpResource) {
                $this->info('Processing floating ip ' . $floatingIpResource->floatingIp->id);
                $nic = $floatingIpResource->resource;
                try {
                    $ipAddress = $nic->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first();
                    if (!$this->option('test-run')) {
                        $floatingIpResource->resource()->associate($ipAddress);
                        $floatingIpResource->save();
                    }
                    $this->info('Floating Ip ' . $floatingIpResource->floatingIp->id . ' associated with ' . $ipAddress->id);
                } catch (\Exception $e) {
                    $this->info(
                        sprintf(
                            'Floating Ip %s failed to associate with %s with error: %s',
                            $floatingIpResource->floatingIp->id,
                            isset($ipAddress) ? $ipAddress->id : 0,
                            $e->getMessage(),
                        )
                    );
                }
            });
    }
}

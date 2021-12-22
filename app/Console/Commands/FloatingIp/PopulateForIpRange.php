<?php

namespace App\Console\Commands\FloatingIp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Vpc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class PopulateForIpRange extends Command
{
    protected $signature = 'floating-ip:populate-for-ip-range {vpc} {az} {--ip-range=*} {--dry-run} {--force}';
    protected $description = 'Adds FIPs to VPC for given IP range in CIDR format, e.g. 1.2.3.0/24';

    public function handle()
    {
        $vpcArg = $this->argument('vpc');
        $vpc = Vpc::find($vpcArg);
        if (!$vpc) {
            $this->error('Cannot find vpc ' . $vpcArg);
            return Command::FAILURE;
        }

        $azArg = $this->argument('az');
        $az = AvailabilityZone::find($azArg);
        if (!$az) {
            $this->error('Cannot find availability zone ' . $azArg);
            return Command::FAILURE;
        }

        $ipRanges = $this->option('ip-range');
        foreach ($ipRanges as $ipRange) {
            $subnet = Subnet::fromString($ipRange);
            if ($subnet == null) {
                $this->error('Cannot parse IP range ' . $ipRange);
                return Command::FAILURE;
            }

            $ipAddresses = collect();
            $ip = $subnet->getStartAddress();

            $start = true;
            while ($start || $ip = $ip->getNextAddress()) {
                $start = false;
                if ($ip == null || !$subnet->contains($ip)) {
                    break;
                }

                $ipAddresses->add($ip->toString());
            }

            $this->info("Parsed {$ipAddresses->count()} IP addresses to populate");

            if (!$this->option('force') && !$this->confirm('Continue?')) {
                $this->warn('Abort');
                return Command::FAILURE;
            }

            foreach ($ipAddresses as $ipAddress) {
                //check no other FIPs have this IP address
                if (FloatingIp::where('ip_address', $ipAddress)
                        ->count() > 0) {
                    $this->warn('IP address "' . $ipAddress . '" in use, skipping');
                    continue;
                }

                $this->info('Adding IP ' . $ipAddress);

                if (!$this->option('dry-run')) {
                    $floatingIp = new FloatingIp([
                        'vpc_id' => $vpc->id,
                        'availability_zone_id' => $az->id,
                    ]);

                    $floatingIp->ip_address = $ipAddress;
                    $floatingIp->syncSave();
                }
            }
        }

        return Command::SUCCESS;
    }
}

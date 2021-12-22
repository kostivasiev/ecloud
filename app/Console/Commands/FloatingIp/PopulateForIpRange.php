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
    protected $signature = 'floating-ip:populate-for-ip-range {vpc} {az} {--ip-range=*}';
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

                $ipAddresses->add($ip);
            }

            $this->info("Parsed {$ipAddresses->count()} IP addresses to populate");

            foreach ($ipAddresses as $ipAddress) {
                $ipString = $ipAddress->toString();

                //check no other FIPs have this IP address
                if (FloatingIp::where('ip_address', $ipString)
                        ->count() > 0) {
                    $this->warn('IP address "' . $ipString . '" in use, skipping');
                    continue;
                }

                $this->info('Adding IP ' . $ipString);
                $floatingIp = new FloatingIp([
                    'vpc_id' => $vpc->id,
                    'availability_zone_id' => $az->id,
                ]);

                $floatingIp->ip_address = $ipString;
                $floatingIp->syncSave();
            }
        }

        return Command::SUCCESS;
    }
}

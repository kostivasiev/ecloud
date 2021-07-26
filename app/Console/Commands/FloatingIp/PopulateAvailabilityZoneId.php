<?php

namespace App\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIp;
use Illuminate\Console\Command;

class PopulateAvailabilityZoneId extends Command
{
    protected $signature = 'floating-ip:populate-availability-zone-id';

    protected $description = 'Back-fill existing floating IP\'s with availability_zone_id property';

    public function handle()
    {
        foreach (FloatingIp::all() as $floatingIp) {
            $availabilityZone = $floatingIp->vpc->region->availabilityZones->first();

            if (!$availabilityZone) {
                $this->error('Failed to set availability zone ID for floating IP ' . $floatingIp->id);
                return Command::FAILURE;
            }

            $floatingIp->availabilityZone()->associate($availabilityZone);
            $floatingIp->save();

            $this->line('FloatingIp ' . $floatingIp->id . ' availability zone updated to ' . $availabilityZone->id);
        }

        return Command::SUCCESS;
    }
}

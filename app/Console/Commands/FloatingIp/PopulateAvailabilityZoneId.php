<?php

namespace App\Console\Commands\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Console\Commands\Command;

class PopulateAvailabilityZoneId extends Command
{
    protected $signature = 'floating-ip:populate-availability-zone-id';

    protected $description = 'Back-fill existing floating IP\'s with availability_zone_id property';

    public function handle()
    {
        $errors = [];

        foreach (FloatingIp::all() as $floatingIp) {
            try {
                $availabilityZone = $floatingIp->vpc->region->availabilityZones->first();
            } catch (\Exception $exception) {
                $this->error('Failed to set availability zone ID for floating IP ' . $floatingIp->id . ': ' . $exception->getMessage());
                $errors[] = $floatingIp->id;
                continue;
            }

            if ($availabilityZone) {
                $floatingIp->availabilityZone()->associate($availabilityZone);
                $floatingIp->save();
                $this->line('FloatingIp ' . $floatingIp->id . ' availability zone updated to ' . $availabilityZone->id);
            }
        }

        if (count($errors) > 0) {
            $this->info(count($errors) . ' errors found, id\'s: '  . implode(',', $errors));
        }

        return Command::SUCCESS;
    }
}

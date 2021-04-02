<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDhcps extends Job
{
    use Batchable;

    private Vpc $vpc;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->vpc->id]);

        $vpc = $this->vpc;

        $this->vpc->region->availabilityZones()->each(function ($availabilityZone) use ($vpc) {
            if ($vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->count() > 0) {
                Log::info('DHCP already exists for AZ ' . $availabilityZone->id . ', skipping');
                return;
            }
            $dhcp = app()->make(Dhcp::class);
            $dhcp->vpc()->associate($vpc);
            $dhcp->availabilityZone()->associate($availabilityZone);
            $dhcp->save();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->vpc->id]);
    }
}

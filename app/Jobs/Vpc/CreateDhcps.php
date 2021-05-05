<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDhcps extends Job
{
    use Batchable, LoggableModelJob;

    private Vpc $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    public function handle()
    {
        $vpc = $this->model;
        $this->model->region->availabilityZones()->each(function ($availabilityZone) use ($vpc) {
            if ($vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->count() > 0) {
                Log::info('DHCP already exists for AZ ' . $availabilityZone->id . ', skipping');
                return;
            }
            $dhcp = app()->make(Dhcp::class);
            $dhcp->vpc()->associate($vpc);
            $dhcp->availabilityZone()->associate($availabilityZone);
            $dhcp->save();
        });
    }
}

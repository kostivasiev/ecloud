<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDHCPs extends Job
{
    use Batchable;

    private Vpc $vpc;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $vpc = $this->vpc;

        $this->vpc->region->availabilityZones()->each(function ($availabilityZone) use ($vpc) {
            $dhcp = app()->make(Dhcp::class);
            $dhcp->vpc()->associate($vpc);
            $dhcp->availabilityZone()->associate($availabilityZone);
            $dhcp->save();
        });

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}

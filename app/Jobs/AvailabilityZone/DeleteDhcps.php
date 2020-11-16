<?php

namespace App\Jobs\AvailabilityZone;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use Illuminate\Support\Facades\Log;

class DeleteDhcps extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);
        $availabilityZone = AvailabilityZone::withTrashed()->findOrFail($this->data['availability_zone_id']);
        $availabilityZone->dhcps()->each(function ($dhcp) {
            $dhcp->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}

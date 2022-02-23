<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\Dhcp;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDhcp extends TaskJob
{
    public function handle()
    {
        $router = $this->task->resource;
        
        $availabilityZone = $router->availabilityZone;
        $vpc = $router->vpc;

        if ($vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->count() > 0) {
            $this->info('DHCP already exists for AZ ' . $availabilityZone->id . ', skipping');
            return;
        }
        $dhcp = app()->make(Dhcp::class);
        $dhcp->vpc()->associate($vpc);
        $dhcp->availabilityZone()->associate($availabilityZone);
        $dhcp->syncSave([
            'router_id' => $router->id,
        ]);
    }
}

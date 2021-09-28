<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDhcp extends Job
{
    use Batchable, LoggableModelJob;

    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        $availabilityZone = $this->model->availabilityZone;
        $vpc = $this->model->vpc;

        if ($vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->count() > 0) {
            Log::info('DHCP already exists for AZ ' . $availabilityZone->id . ', skipping');
            return;
        }
        $dhcp = app()->make(Dhcp::class);
        $dhcp->vpc()->associate($vpc);
        $dhcp->availabilityZone()->associate($availabilityZone);
        $dhcp->syncSave();
    }
}

<?php

namespace App\Jobs\Vip;

use App\Jobs\Job;
use App\Models\V2\IpAddress;
use App\Models\V2\Vip;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignIpAddress extends Job
{
    use Batchable, LoggableModelJob;

    private Vip $model;

    public function __construct(Vip $vip)
    {
        $this->model = $vip;
    }

    public function handle()
    {
        $vip = $this->model;

        if (!$vip->ipAddress()->exists()) {
            $ipAddress = $vip->assignClusterIp();
            Log::info('IP Address ' . $ipAddress->id . ' (' . $ipAddress->getIPAddress() . ') assigned to VIP ' . $vip->id);
        }
    }
}

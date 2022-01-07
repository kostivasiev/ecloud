<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use Illuminate\Support\Facades\Log;

class AssignIpAddress extends TaskJob
{
    public function handle()
    {
        $vip = $this->task->resource;

        if (!$vip->ipAddress()->exists()) {
            $ipAddress = $vip->assignClusterIp();
            Log::info('IP Address ' . $ipAddress->id . ' (' . $ipAddress->getIPAddress() . ') assigned to VIP ' . $vip->id);
        }
    }
}

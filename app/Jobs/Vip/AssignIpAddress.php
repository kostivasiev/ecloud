<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;

class AssignIpAddress extends TaskJob
{
    /**
     * Assign an IP address to the vip
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (!$vip->ipAddress()->exists()) {
            $ipAddress = $vip->assignClusterIp();
            $this->info('IP Address ' . $ipAddress->id . ' (' . $ipAddress->getIPAddress() . ') assigned to VIP ' . $vip->id);
        }
    }
}

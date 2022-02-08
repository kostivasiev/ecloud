<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class UnassignClusterIp extends TaskJob
{
    /**
     * Assign an IP address to the vip
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (!$vip->ipAddress()->exists()) {
            $this->info('No ' . IpAddress::TYPE_CLUSTER . ' IP Address associated with VIP, skipping.');
            return;
        }

        $ipAddress = $vip->ipAddress;
        $this->info('Unassigning ' . IpAddress::TYPE_CLUSTER . ' IP address ' . $ipAddress->id . ' (' . $ipAddress->getIPAddress() . ') from VIP ' . $vip->id);

        $vip->ipAddress()->dissociate();
        $vip->save();

        $ipAddress->delete();
    }
}

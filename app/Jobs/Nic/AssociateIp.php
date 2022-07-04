<?php

namespace App\Jobs\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class AssociateIp extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;
        $ipAddress = IpAddress::find($this->task->data['ip_address_id']);
        if (!$ipAddress) {
            $this->fail(new \Exception('The supplied IP Address cannot be found'));
            return;
        }
        $nic->ipAddresses()->save($ipAddress);
        $this->info('Associated NIC ' . $nic->id . ' with IP Address ' . $this->task->data['ip_address_id']);
    }
}

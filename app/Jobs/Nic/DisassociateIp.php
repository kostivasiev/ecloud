<?php

namespace App\Jobs\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class DisassociateIp extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;
        $ipAddress = IpAddress::find($this->task->data['ip_address_id']);
        if (!$ipAddress) {
            $this->fail(new \Exception('The supplied IP Address cannot be found'));
            return;
        }
        $nic->ipAddresses()->detach($ipAddress);
        $this->info('Disassociated NIC ' . $nic->id . ' from IP Address ' . $this->task->data['ip_address_id']);
    }
}

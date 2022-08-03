<?php

namespace App\Jobs\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class CheckIpAssignment extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;

        if ($nic->ipAddresses()->withType(IpAddress::TYPE_CLUSTER)->exists()) {
            $this->fail(new \Exception('Failed to delete NIC ' . $nic->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected'));
            return false;
        }
    }
}

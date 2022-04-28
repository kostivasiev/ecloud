<?php

namespace App\Jobs\Nic;

use App\Jobs\TaskJob;

class Deploy extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;

        if (!empty($nic->mac_address)) {
            $this->info('Resource already exists, skipping.');
            return;
        }

        $instance = $nic->instance;

        $response = $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc->id .
            '/instance/' . $instance->id .
            '/nic',
            [
                'json' => [
                    'networkId' => $nic->network_id,
                ],
            ]
        );

        $macAddress = json_decode($response?->getBody()?->getContents())?->macAddress;
        if (empty($macAddress)) {
            $this->fail(new \Exception('Failed to determine the NIC\'s MAC address'));
            return;
        }

        $nic->mac_address = $macAddress;

        $nic->save();

        $this->info('Created new NIC ' . $nic->id . ' on instance ' . $instance->id . ' with MAC address ' . $nic->mac_address);
    }
}

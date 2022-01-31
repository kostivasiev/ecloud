<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class UnbindIpAddress extends TaskJob
{
    /**
     * Patch a Tier-1 segment port with an IP address binding
     * @see: https://vdc-download.vmware.com/vmwb-repository/dcr-public/787988e9-6348-4b2a-8617-e6d672c690ee/a187360c-77d5-4c0c-92a8-8e07aa161a27/api_includes/method_PatchTier1SegmentPort.html
     * @return bool|void
     * @throws \Exception
     */
    public function handle()
    {
        $nic = $this->task->resource;

        $ipAddress = IpAddress::findOrFail($this->task->data['ip_address_id']);

        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $router->availabilityZone->nsxService();
        $nic->refresh();

        $ipAddresses = $nic->ipAddresses->where('type', IpAddress::TYPE_CLUSTER);

        $ipAddresses = $ipAddresses->reject(function ($nicIpAddress) use ($ipAddress) {
            return $nicIpAddress->id == $ipAddress->id;
        });

        $nsxService->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/segments/' . $network->id .
            '/ports/' . $nic->id,
            [
                'json' => [
                    'resource_type' => 'SegmentPort',
                    'address_bindings' =>  $ipAddresses->values()->map(function ($ipAddress) use ($nic) {
                        return [
                            'ip_address' => $ipAddress->ip_address,
                            'mac_address' => $nic->mac_address
                        ];
                    })->toArray()
                ]
            ]
        );

        $nic->ipAddresses()->detach($ipAddresses);

        $this->info('Address binding removed for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $ipAddress->ip_address);
    }
}

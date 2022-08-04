<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;
use Illuminate\Support\Facades\Log;

class CreateDHCPLease extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;
        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $router->availabilityZone->nsxService();

        /**
         * Get DHCP static bindings to determine used IP addresses on the network
         * @see https://185.197.63.88/policy/api_includes/method_ListSegmentDhcpStaticBinding.html
         */
        $cursor = null;
        $assignedIpsNsx = collect();
        do {
            $response = $nsxService->get('/policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/dhcp-static-binding-configs?cursor=' . $cursor);
            $response = json_decode($response->getBody()->getContents());
            foreach ($response->results as $dhcpStaticBindingConfig) {
                if (!empty($nic->ip_address)
                    && $dhcpStaticBindingConfig->id == $nic->id
                    && $dhcpStaticBindingConfig->ip_address == $nic->ip_address) {
                    Log::info("DHCP IP address already assigned, skipping");
                    return true;
                }
                $assignedIpsNsx->push($dhcpStaticBindingConfig->ip_address);
            }
            $cursor = $response->cursor ?? null;
        } while (!empty($cursor));

        if (!$nic->ipAddresses()->withType(IpAddress::TYPE_DHCP)->exists()) {
            $ipAddress = $nic->assignIpAddress($assignedIpsNsx->toArray(), IpAddress::TYPE_DHCP);
        } else {
            $ipAddress = $nic->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first();
        }

        $nic->refresh();

        $nsxService->put(
            '/policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id
            . '/dhcp-static-binding-configs/' . $nic->id,
            [
                'json' => [
                    'resource_type' => 'DhcpV4StaticBindingConfig',
                    'mac_address' => $nic->mac_address,
                    'ip_address' => $ipAddress->ip_address,
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $router->vpc->id
                        ]
                    ]
                ]
            ]
        );

        $this->info('DHCP static binding created for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $ipAddress->ip_address);
    }
}

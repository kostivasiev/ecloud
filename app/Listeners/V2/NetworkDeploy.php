<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class NetworkDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param NetworkCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(NetworkCreated $event)
    {
        $network = $event->network;

        if (empty($network->router)) {
            throw new \Exception('Failed to load network\'s router');
        }

        if (empty($network->router->vpc->dhcp)) {
            throw new \Exception('Failed to load DHCP for VPC');
        }

        try {
            $network->availabilityZone->nsxClient()->put(
                'policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey(),
                [
                    'json' => [
                        'resource_type' => 'Segment',
                        'subnets' => [
                            [
                                'gateway_address' => config('defaults.network.subnets.gateway_address'),
                                'dhcp_config' => [
                                    'resource_type' => 'SegmentDhcpV4Config',
                                    'server_address' => config('defaults.network.subnets.dhcp_config.server_address'),
                                    'lease_time' => config('defaults.network.subnets.dhcp_config.lease_time'),
                                    'dns_servers' => config('defaults.network.subnets.dhcp_config.dns_servers')
                                ]
                            ]
                        ],
                        'domain_name' => config('defaults.network.domain_name'),
                        'transport_zone_path' => config('defaults.network.transport_zone_path'),
                        "dhcp_config_path" => "/infra/dhcp-server-configs/" . $network->router->vpc->dhcp->getKey()
                    ]
                ]
            );
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }
    }
}

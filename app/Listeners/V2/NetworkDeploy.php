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

        try {
            // todo: tier-1-id is router id???

            $network->availabilityZone->nsxClient()->put(
                'policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey(), [
                'json' => [
                    //'domain_name' => '', //??
                    'resource_type' => 'Segment',
                    'subnets' => [
                        [
                            'dhcp_config' => [
                                'dns_servers' => [
                                    '81.201.138.244',
                                    '94.229.163.244',
                                ],
                                'lease_time' => 604800,
                                'resource_type' => 'SegmentDhcpV4Config',
                                'server_address' => '10.0.0.1/24' // DHCP server address? "Second usable address from subnets.0.network"
                            ],
                            'dhcp_ranges' => config('defaults.network.dhcp_ranges'),
                            'gateway_address' => '10.0.0.1/24',
                        ]
                    ]
                ]
            ]);
        } catch (GuzzleException $exception) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            exit(print_r(
                $json
            ));
            throw new \Exception($json);
        }
    }
}

/**
 *
 *                 'json' => [
 * //'domain_name' => '', //??
 * 'resource_type' => 'Segment',
 * 'subnets' => [
 * 'dhcp_config' => [
 * 'dns_servers'    => config('defaults.network.dns_servers'),
 * 'lease_time' => config('defaults.network.lease_time'),
 * 'resource_type' => 'SegmentDhcpV4Config',
 * 'server_address' => config('defaults.network.server_address') // DHCP server address? "Second usable address from subnets.0.network"
 * ],
 * 'dhcp_ranges' => config('defaults.network.dhcp_ranges'),
 * 'gateway_address' => config('defaults.network.gateway_address'),
 * ]
 * ]
 */

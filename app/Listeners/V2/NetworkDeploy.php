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

        //resource_type = Segment
        //domain_name
        //subnets = array of SegmentSubnet

        try {
            ///
            // todo: tier-1-id is router id???

            $network->availabilityZone->nsxClient()->put(
                'policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey(), [
                'json' => [
                    'domain_name' => '', //??
                    'resource_type' => 'Segment',
                    'subnets' => [
                        'dhcp_config' => [
                            'dns_servers'    => [
                                'primary'   => '81.201.138.244',
                                'secondary' => '94.229.163.244',
                            ],
                            'lease_time' => 604800,
                            'resource_type' => 'SegmentDhcpV4Config',
                            'server_address' => '' // DHCP server address? "Second usable address from subnets.0.network"
                        ],
                        'dhcp_ranges' => ['10.0.0.0/24'],
                        'gateway_address' => '10.0.0.1', //10.0.0.0/24 ??
                    ]


                ]
            ]);
        } catch (GuzzleException $exception) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            throw new \Exception($json);
        }
    }
}

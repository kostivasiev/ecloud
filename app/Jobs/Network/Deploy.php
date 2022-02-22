<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use Exception;
use IPLib\Range\Subnet;

class Deploy extends TaskJob
{
    public function handle()
    {
        $network = $this->task->resource;

        $dhcp = $network->router->vpc->dhcps()->where('availability_zone_id', $network->router->availability_zone_id)->first();
        if (empty($dhcp)) {
            $this->fail(new Exception('Unable to locate VPC DHCP server for router availability zone'));
            return;
        }

        //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
        $gatewayAddress = $network->getGatewayAddress();
        $dhcpServerAddress = $network->getDhcpServerAddress();
        $networkPrefix = $network->getNetworkPrefix();

        $gatewayAddressWithPrefix = $gatewayAddress->toString() . '/' . $networkPrefix;
        $dhcpServerAddressWithPrefix = $dhcpServerAddress->toString() . '/' . $networkPrefix;

        $message = 'Deploying Network: ' . $network->id . ': ';
        $this->debug($message . 'Gateway Address: ' . $gatewayAddressWithPrefix);
        $this->debug($message . 'DHCP Server Address: ' . $dhcpServerAddressWithPrefix);
        $this->debug($message . 'DHCP ID: ' . $dhcp->id);

        $network->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id,
            [
                'json' => [
                    'resource_type' => 'Segment',
                    'subnets' => [
                        [
                            'gateway_address' => $gatewayAddressWithPrefix,
                            'dhcp_config' => [
                                'resource_type' => 'SegmentDhcpV4Config',
                                'server_address' => $dhcpServerAddressWithPrefix,
                                'lease_time' => config('defaults.network.subnets.dhcp_config.lease_time'),
                                'dns_servers' => config('defaults.network.subnets.dhcp_config.dns_servers')
                            ]
                        ]
                    ],
                    'domain_name' => config('defaults.network.domain_name'),
                    'dhcp_config_path' => '/infra/dhcp-server-configs/' . $dhcp->id,
                    'advanced_config' => [
                        'connectivity' => 'ON'
                    ],
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $network->router->vpc->id
                        ]
                    ]
                ]
            ]
        );
    }
}

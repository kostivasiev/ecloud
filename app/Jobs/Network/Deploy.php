<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class Deploy extends Job
{
    use Batchable;

    private Network $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->network->id]);

        $dhcp = $this->network->router->vpc->dhcps()->where('availability_zone_id', $this->network->router->availability_zone_id)->first();
        if (empty($dhcp)) {
            $this->fail(new Exception('Unable to locate VPC DHCP server for router availability zone'));
            return;
        }

        $subnet = Subnet::fromString($this->network->subnet);
        //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
        $networkAddress = $subnet->getStartAddress();
        $gatewayAddress = $networkAddress->getNextAddress();
        $dhcpServerAddress = $gatewayAddress->getNextAddress();
        $message = 'Deploying Network: ' . $this->network->id . ': ';
        Log::info($message . 'Gateway Address: ' . $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix());
        Log::info($message . 'DHCP Server Address: ' . $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix());
        Log::info($message . 'DHCP ID: ' . $dhcp->id);

        $this->network->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/tier-1s/' . $this->network->router->id . '/segments/' . $this->network->id,
            [
                'json' => [
                    'resource_type' => 'Segment',
                    'subnets' => [
                        [
                            'gateway_address' => $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix(),
                            'dhcp_config' => [
                                'resource_type' => 'SegmentDhcpV4Config',
                                'server_address' => $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix(),
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
                            'tag' => $this->network->router->vpc->id
                        ]
                    ]
                ]
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}

<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class NetworkDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    const ROUTER_RETRY_ATTEMPTS = 10;

    const ROUTER_RETRY_DELAY = 10;

    /**
     * @param NetworkCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(NetworkCreated $event)
    {
        $network = $event->network;
        $router = $network->router;

        if (!$router->available) {
            if ($this->attempts() <= static::ROUTER_RETRY_ATTEMPTS) {
                $this->release(static::ROUTER_RETRY_DELAY);
                Log::info('Attempted to create Network (' . $network->getKey() .
                    ') but Router (' . $router->getKey() . ') was not available, will retry shortly');
                return;
            } else {
                $message = 'Timed out waiting for Router (' . $router->getKey() .
                    ') to become available for Network (' . $network->getKey() . ') deployment';
                Log::error($message);
                $this->fail(new \Exception($message));
                return;
            }
        }

        try {
            $subnet = \IPLib\Range\Subnet::fromString($network->subnet);
            //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
            $networkAddress = $subnet->getStartAddress();
            $gatewayAddress = $networkAddress->getNextAddress();
            $dhcpServerAddress = $gatewayAddress->getNextAddress();
            $message = 'Deploying Network: ' . $network->id . ': ';
            Log::info($message . 'Gateway Address: ' . $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix());
            Log::info($message . 'DHCP Server Address: ' . $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix());

            $router->availabilityZone->nsxService()->put(
                'policy/api/v1/infra/tier-1s/' . $router->getKey() . '/segments/' . $network->getKey(),
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
                        'dhcp_config_path' => '/infra/dhcp-server-configs/' . $router->vpc->dhcp->getKey(),
                        'advanced_config' => [
                            'connectivity' => 'ON'
                        ],
                        'tags' => [
                            [
                                'scope' => config('defaults.tag.scope'),
                                'tag' => $router->vpc->getKey()
                            ]
                        ]
                    ]
                ]
            );
        } catch (GuzzleException $exception) {
            //Segment already exists. Hacky fix, as the listener is fired twice due to rincewind
            if (json_decode($exception->getResponse()->getBody()->getContents())->error_code == 500127) {
                Log::info('Attempted to create network segment ' . $network->getKey() . ' but it already exists.');
                return;
            }
            $message = 'NetworkDeploy failed with : ' .  $exception->getResponse()->getBody()->getContents();
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }
    }
}

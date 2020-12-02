<?php

namespace App\Listeners\V2\Network;

use App\Events\V2\Network\Created;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    const ROUTER_RETRY_ATTEMPTS = 10;
    const ROUTER_RETRY_DELAY = 10;
    public $tries = 20;

    /**
     * @param Created $event
     * @return void
     * @throws Exception
     */
    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $network = $event->model;
        $router = $network->router;

        if (!$router->available) {
            if ($this->attempts() <= static::ROUTER_RETRY_ATTEMPTS) {
                $this->release(static::ROUTER_RETRY_DELAY);
                Log::info('Attempted to create Network (' . $network->id .
                    ') but Router (' . $router->id . ') was not available, will retry shortly');
                return;
            } else {
                $message = 'Timed out waiting for Router (' . $router->id .
                    ') to become available for Network (' . $network->id . ') deployment';
                Log::error($message);
                $network->setSyncFailureReason($message);
                $this->fail(new Exception($message));
                return;
            }
        }

        $subnet = Subnet::fromString($network->subnet);
        //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
        $networkAddress = $subnet->getStartAddress();
        $gatewayAddress = $networkAddress->getNextAddress();
        $dhcpServerAddress = $gatewayAddress->getNextAddress();
        $message = 'Deploying Network: ' . $network->id . ': ';
        Log::info($message . 'Gateway Address: ' . $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix());
        Log::info($message . 'DHCP Server Address: ' . $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix());
        Log::info($message . 'DHCP ID: ' . $router->vpc->dhcp->id);

        try {
            $router->availabilityZone->nsxService()->put(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id,
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
                        'dhcp_config_path' => '/infra/dhcp-server-configs/' . $router->vpc->dhcp->id,
                        'advanced_config' => [
                            'connectivity' => 'ON'
                        ],
                        'tags' => [
                            [
                                'scope' => config('defaults.tag.scope'),
                                'tag' => $router->vpc->id
                            ]
                        ]
                    ]
                ]
            );
        } catch (RequestException $exception) {
            //Segment already exists. Hacky fix, as the listener is fired twice due to rincewind
            if ($exception->hasResponse()) {
                $error = json_decode($exception->getResponse()->getBody()->getContents());
                if ($error->error_code == 500127) {
                    $message = 'Attempted to create network segment ' . $network->id .
                        ' but it already exists.' . PHP_EOL .
                        'NSX Error : ' . $error->error_message;
                    Log::error($message);
                    $network->setSyncFailureReason($message);
                    return;
                }
            }

            $message = 'Unhandled error for ' . $network->id;
            Log::error($message, [$exception]);
            $network->setSyncFailureReason($message);
            return;
        }
        $network->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

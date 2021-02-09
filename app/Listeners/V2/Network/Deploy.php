<?php

namespace App\Listeners\V2\Network;

use App\Events\V2\Network\Saved;
use App\Models\V2\Network;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    const RETRY_DELAY = 10;
    const ROUTER_RETRY_ATTEMPTS = 10;
    const ROUTER_RETRY_DELAY = 10;
    public $tries = 20;

    /**
     * @param Saved $event
     * @return void
     * @throws Exception
     */
    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        /** @var Network $network */
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
            $router->availabilityZone->nsxService()->patch(
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

            // Security profile
            Log::info('Updating security profile');
            $response = $router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-security-profile-binding-maps',
            );
            $response = json_decode($response->getBody()->getContents(), true);
            $response['results'][0]['segment_security_profile_path'] = '/infra/segment-security-profiles/ecloud-segment-security-profile';
            $response['results'][0]['spoofguard_profile_path'] = '/infra/spoofguard-profiles/ecloud-spoofguard-profile';
            $router->availabilityZone->nsxService()->patch(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-security-profile-binding-maps/' . $response['results'][0]['id'],
                ['json' => $response['results'][0]]
            );
            Log::info('Updated security profile ' . $response['results'][0]['id']);

            // Discovery profile
            Log::info('Updating discovery profile');
            $response = $router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-discovery-profile-binding-maps',
            );
            $response = json_decode($response->getBody()->getContents(), true);
            $response['results'][0]['ip_discovery_profile_path'] = '/infra/ip-discovery-profiles/ecloud-ip-discovery-profile';
            $response['results'][0]['mac_discovery_profile_path'] = '/infra/mac-discovery-profiles/ecloud-mac-discovery-profile';
            $router->availabilityZone->nsxService()->patch(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-discovery-profile-binding-maps/' . $response['results'][0]['id'],
                ['json' => $response['results'][0]]
            );
            Log::info('Updated discovery profile ' . $response['results'][0]['id']);

            // QOS profile
            Log::info('Updating QOS profile');
            $response = $router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-qos-profile-binding-maps',
            );
            $response = json_decode($response->getBody()->getContents(), true);
            $response['results'][0]['qos_profile_path'] = '';
            $router->availabilityZone->nsxService()->patch(
                'policy/api/v1/infra/tier-1s/' . $router->id . '/segments/' . $network->id . '/segment-qos-profile-binding-maps/' . $response['results'][0]['id'],
                ['json' => $response['results'][0]]
            );
            Log::info('Updated QOS profile ' . $response['results'][0]['id']);
        } catch (RequestException $exception) {
            //Segment already exists. Hacky fix, as the listener is fired twice due to rincewind
            if ($exception->hasResponse()) {
                $error = json_decode($exception->getResponse()->getBody()->getContents());
                if ($error->error_code == 500127) {
                    $message = 'Attempted to create network segment ' . $network->id .
                        ' but it already exists.' . PHP_EOL .
                        'NSX Error : ' . $error->error_message;
                    Log::error($message);
                    $network->setSyncFailureReason($message . PHP_EOL . $exception->getResponse()->getBody());
                    return;
                }

                $message = 'Unhandled error response for ' . $network->id;
                Log::error($message, (array)$error);
                $network->setSyncFailureReason($message . PHP_EOL . $exception->getResponse()->getBody());
                $this->fail($exception);
                return;
            }

            $message = 'Unhandled error for ' . $network->id;
            Log::error($message, [$exception]);
            $network->setSyncFailureReason($message . PHP_EOL . $exception->getMessage());
            $this->fail($exception);
            return;
        }

        $network->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

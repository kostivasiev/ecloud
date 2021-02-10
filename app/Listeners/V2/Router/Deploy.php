<?php

namespace App\Listeners\V2\Router;

use App\Events\V2\Router\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @see https://185.197.63.88/policy/api_includes/method_CreateOrReplaceTier1.html
     * @param Created $event
     * @return void
     * @throws \Exception
     */
    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        /** @var Router $router */
        $router = $event->model;

        /** @var AvailabilityZone $availabilityZone */
        $availabilityZone = $router->availabilityZone;
        if (!$availabilityZone) {
            $message = 'Failed to find AZ for router ' . $router->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        if (empty($router->routerThroughput)) {
            $this->fail(new \Exception('Failed determine router throughput settings for router ' . $router->id));
            return;
        }

        $nsxService = $availabilityZone->nsxService();
        if (!$nsxService) {
            $message = 'Failed to find NSX Service for router ' . $router->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Load default T0 for the AZ
        $tier0SearchResponse = $nsxService->get(
            '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:az-default'
        );
        $tier0SearchResponse = json_decode($tier0SearchResponse->getBody()->getContents());

        if ($tier0SearchResponse->result_count != 1) {
            $this->fail(new \Exception('No tagged T0 could be found'));
            return;
        }

        $tier0 = $tier0SearchResponse->results[0];

        $vpcTag = [
            'scope' => config('defaults.tag.scope'),
            'tag' => $router->vpc_id
        ];

        $gatewayQosProfileSearchResponse = $nsxService->get(
            'policy/api/v1/search/query?query=resource_type:GatewayQosProfile'
            . '%20AND%20committed_bandwitdth:' . $router->routerThroughput->committed_bandwidth
            . '%20AND%20burst_size:' . ($router->routerThroughput->burst_size * 1000)
        );

        $gatewayQosProfileSearchResponse = json_decode($gatewayQosProfileSearchResponse->getBody()->getContents());

        if ($gatewayQosProfileSearchResponse->result_count != 1) {
            $message = 'Failed to determine gateway QoS profile for router ' . $router->id . ', with router_throughput_id ' . $router->routerThroughput->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Deploy the router
        $nsxService->put('policy/api/v1/infra/tier-1s/' . $router->id, [
            'json' => [
                'tier0_path' => $tier0->path,
                'tags' => [$vpcTag],
                'route_advertisement_types' => [
                    'TIER1_IPSEC_LOCAL_ENDPOINT',
                    'TIER1_STATIC_ROUTES',
                    'TIER1_NAT'
                ],
                'qos_profile' => [
                    'egress_qos_profile_path' => $gatewayQosProfileSearchResponse->results[0]->path,
                    'ingress_qos_profile_path' => $gatewayQosProfileSearchResponse->results[0]->path
                ]
            ],
        ]);

        // Deploy the router locale
        $nsxService->put('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id, [
            'json' => [
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $nsxService->getEdgeClusterId(),
                'tags' => [$vpcTag]
            ],
        ]);

        // Update the routers default firewall rule to Reject
        $response = $nsxService->get('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule');
        $original = json_decode($response->getBody()->getContents(), true);
        $original['action'] = 'REJECT';
        $original = array_filter($original, function ($key) {
            return strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);
        $nsxService->patch(
            'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule',
            [
                'json' => $original
            ]
        );
        $router->setSyncCompleted();
        $router->deployed = true;
        $router->save();

        $router->networks()->each(function ($network) {
            /** @var Network $network */
            $network->subnet = config('defaults.network.subnets.range');
            $network->save();
            event(new \App\Events\V2\Network\Created($network));
        });

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;

class Deploy extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;

        if (empty($router->routerThroughput)) {
            $this->fail(new \Exception('Failed determine router throughput settings for router ' . $router->id));
            return;
        }

        $gatewayQosProfileSearchResponse = $router->availabilityZone->nsxService()->get(
            'policy/api/v1/search/query?query=resource_type:GatewayQosProfile'
            . '%20AND%20committed_bandwitdth:' . $router->routerThroughput->committed_bandwidth
        );

        $gatewayQosProfileSearchResponse = json_decode($gatewayQosProfileSearchResponse->getBody()->getContents());

        if ($gatewayQosProfileSearchResponse->result_count != 1) {
            $message = 'Failed to determine gateway QoS profile for router ' . $router->id . ', with router_throughput_id ' . $router->routerThroughput->id;
            $this->error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $payload = [
            'tags' => [
                [
                    'scope' => config('defaults.tag.scope'),
                    'tag' => $router->vpc_id,
                ],
            ],
            'route_advertisement_types' => [
                'TIER1_IPSEC_LOCAL_ENDPOINT',
                'TIER1_STATIC_ROUTES',
                'TIER1_NAT'
            ],
            'qos_profile' => [
                'egress_qos_profile_path' => $gatewayQosProfileSearchResponse->results[0]->path,
                'ingress_qos_profile_path' => $gatewayQosProfileSearchResponse->results[0]->path
            ]
        ];

        if ($router->is_management) {
            $payload['route_advertisement_types'][] = 'TIER1_CONNECTED';
        }

        $exists = true;
        try {
            $router->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $router->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $exists = false;
            } else {
                throw $e;
            }
        }

        if (!$exists) {
            $tier0Tag = ($router->vpc->advanced_networking) ?
                config('defaults.tag.networking.advanced') :
                config('defaults.tag.networking.default');
            if ($router->is_management) {
                $tier0Tag = ($router->vpc->advanced_networking) ?
                    config('defaults.tag.networking.management.advanced') :
                    config('defaults.tag.networking.management.default');
            }

            // Load default T0 for the AZ
            $tier0SearchResponse = $router->availabilityZone->nsxService()->get(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:' . $tier0Tag
            );
            $tier0SearchResponse = json_decode($tier0SearchResponse->getBody()->getContents());

            if ($tier0SearchResponse->result_count != 1) {
                $this->fail(new \Exception('No tagged T0 could be found'));
                return;
            }

            $payload['tier0_path'] = $tier0SearchResponse->results[0]->path;
        }

        // Deploy/update the router
        $router->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $router->id, [
            'json' => $payload,
        ]);
    }
}

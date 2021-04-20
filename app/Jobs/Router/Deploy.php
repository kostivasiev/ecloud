<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;
    
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        if (empty($this->router->routerThroughput)) {
            $this->fail(new \Exception('Failed determine router throughput settings for router ' . $this->router->id));
            return;
        }

        $gatewayQosProfileSearchResponse = $this->router->availabilityZone->nsxService()->get(
            'policy/api/v1/search/query?query=resource_type:GatewayQosProfile'
            . '%20AND%20committed_bandwitdth:' . $this->router->routerThroughput->committed_bandwidth
        );

        $gatewayQosProfileSearchResponse = json_decode($gatewayQosProfileSearchResponse->getBody()->getContents());

        if ($gatewayQosProfileSearchResponse->result_count != 1) {
            $message = 'Failed to determine gateway QoS profile for router ' . $this->router->id . ', with router_throughput_id ' . $this->router->routerThroughput->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $payload = [
            'tags' => [
                [
                    'scope' => config('defaults.tag.scope'),
                    'tag' => $this->router->vpc_id,
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

        $exists = true;
        try {
            $this->router->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $this->router->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $exists = false;
            } else {
                throw $e;
            }
        }

        if (!$exists) {
            // Load default T0 for the AZ
            $tier0SearchResponse = $this->router->availabilityZone->nsxService()->get(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:az-default'
            );
            $tier0SearchResponse = json_decode($tier0SearchResponse->getBody()->getContents());

            if ($tier0SearchResponse->result_count != 1) {
                $this->fail(new \Exception('No tagged T0 could be found'));
                return;
            }

            $payload['tier0_path'] = $tier0SearchResponse->results[0]->path;
        }

        // Deploy/update the router
        $this->router->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $this->router->id, [
            'json' => $payload,
        ]);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}

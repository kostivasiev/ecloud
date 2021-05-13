<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;
    
    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->model->routerThroughput)) {
            $this->fail(new \Exception('Failed determine router throughput settings for router ' . $this->model->id));
            return;
        }

        $gatewayQosProfileSearchResponse = $this->model->availabilityZone->nsxService()->get(
            'policy/api/v1/search/query?query=resource_type:GatewayQosProfile'
            . '%20AND%20committed_bandwitdth:' . $this->model->routerThroughput->committed_bandwidth
        );

        $gatewayQosProfileSearchResponse = json_decode($gatewayQosProfileSearchResponse->getBody()->getContents());

        if ($gatewayQosProfileSearchResponse->result_count != 1) {
            $message = 'Failed to determine gateway QoS profile for router ' . $this->model->id . ', with router_throughput_id ' . $this->model->routerThroughput->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $payload = [
            'tags' => [
                [
                    'scope' => config('defaults.tag.scope'),
                    'tag' => $this->model->vpc_id,
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
            $this->model->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $this->model->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $exists = false;
            } else {
                throw $e;
            }
        }

        if (!$exists) {
            $tier0Tag = ($this->model->vpc->advanced_networking) ? 'az-advanced' : 'az-default';

            // Load default T0 for the AZ
            $tier0SearchResponse = $this->model->availabilityZone->nsxService()->get(
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
        $this->model->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $this->model->id, [
            'json' => $payload,
        ]);
    }
}

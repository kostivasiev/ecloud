<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterCreated;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RouterDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param RouterCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(RouterCreated $event)
    {
        /** @var Router $router */
        $router = $event->router;

        /** @var AvailabilityZone $availabilityZone */
        $availabilityZone = $router->availabilityZone;

        try {
            $nsxService = $availabilityZone->nsxService();

            // Get the routers T0 path
            $response = $nsxService->get('policy/api/v1/infra/tier-0s');
            $response = json_decode($response->getBody()->getContents(), true);
            $path = null;
            foreach ($response['results'] as $tier0) {
                foreach ($tier0['tags'] as $tag) {
                    if ($tag['scope'] == 'ukfast' && $tag['tag'] == 'az-default') {
                        $path = $tier0['path'];
                        break 2;
                    }
                }
            }
            if (empty($path)) {
                throw new \Exception('No tagged T0 could be found');
            }

            $vpcTag = [
                'scope' => config('defaults.tag.scope'),
                'tag' => $router->vpc_id
            ];

            // Deploy the router
            $nsxService->put('policy/api/v1/infra/tier-1s/' . $router->id, [
                'json' => [
                    'tier0_path' => $path,
                    'tags' => [$vpcTag]
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
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }
        $router->deployed = true;
        $router->save();

        $router->networks()->each(function ($network) {
            /** @var Network $network */
            event(new NetworkCreated($network));
        });
    }
}

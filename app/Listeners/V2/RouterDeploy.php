<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class RouterDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param RouterAvailabilityZoneAttach $event
     * @return void
     * @throws \Exception
     */
    public function handle(RouterAvailabilityZoneAttach $event)
    {
        /** @var Router $router */
        $router = $event->router;

        /** @var AvailabilityZone $availabilityZone */
        $availabilityZone = $event->availabilityZone;

        try {
            $nsxClient = $availabilityZone->nsxClient();

            $tag =  [
                'scope' => config('defaults.tag.scope'),
                'tag' => $router->vpc_id
            ];

            $nsxClient->put('policy/api/v1/infra/tier-1s/' . $router->id, [
                'json' => [
                    'tier0_path' => '/infra/tier-0s/T0',
                    'tags' => [$tag]
                ],
            ]);
            $nsxClient->put('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id, [
                'json' => [
                    'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $nsxClient->getEdgeClusterId(),
                    'tags' => [$tag]
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }
        $router->deployed = true;
        $router->firewallRules()->create();
        $router->save();

        $router->networks()->each(function ($network) {
            /** @var Network $network */
            event(new NetworkCreated($network));
        });
    }
}

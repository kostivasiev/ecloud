<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterAvailabilityZoneAttach;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\FirewallRule;
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
            $nsxClient->put('policy/api/v1/infra/tier-1s/' . $router->id, [
                'json' => [
                    'tier0_path' => '/infra/tier-0s/T0',
                ],
            ]);
            $nsxClient->put('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id, [
                'json' => [
                    'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $nsxClient->getEdgeClusterId(),
                ],
            ]);
        } catch (GuzzleException $exception) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            throw new \Exception($json);
        }
        $router->deployed = true;
        $router->save();

        $firewallRule = app()->make(FirewallRule::class);
        $firewallRule->router()->attach($router);
        $firewallRule->save();

        $router->networks()->each(function ($network) {
            /** @var Network $network */
            event(new NetworkCreated($network));
        });
    }
}

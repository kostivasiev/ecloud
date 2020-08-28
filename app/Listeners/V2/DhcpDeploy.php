<?php

namespace App\Listeners\V2;

use App\Events\V2\DhcpCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class DhcpDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param DhcpCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(DhcpCreated $event)
    {
        $dhcp = $event->dhcp;

        $event->dhcp->vpc->region->availabilityZones()->each(function ($availabilityZone) use ($dhcp) {
            try {
                $availabilityZone->nsxClient()->put('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->getKey(), [
                    'json' => [
                        'lease_time' => config('defaults.dhcp.lease_time'),
                        'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                            . $availabilityZone->nsxClient()->getEdgeClusterId(),
                        'resource_type' => 'DhcpServerConfig'
                    ]
                ]);
            } catch (GuzzleException $exception) {
                throw new \Exception($exception->getResponse()->getBody()->getContents());
            }
        });
    }
}

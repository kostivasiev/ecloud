<?php

namespace App\Listeners\V2;

use App\Events\V2\DhcpCreated;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        try {
            $dhcp->availabilityZone->nsxService()->put('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->getKey(), [
                'json' => [
                    'lease_time' => config('defaults.dhcp.lease_time'),
                    'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                        . $dhcp->availabilityZone->nsxService()->getEdgeClusterId(),
                    'resource_type' => 'DhcpServerConfig',
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $dhcp->vpc->getKey()
                        ]
                    ]
                ]
            ]);
        } catch (GuzzleException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }
    }
}

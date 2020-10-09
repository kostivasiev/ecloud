<?php

namespace App\Listeners\V2\Dhcp;

use App\Events\V2\Dhcp\Created;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Created $event
     * @return void
     * @throws \Exception
     */
    public function handle(Created $event)
    {
        $dhcp = $event->model;
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

<?php

namespace App\Listeners\V2\Nsx\Dhcp;

use App\Events\V2\Dhcp\Created;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Create implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Created $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $dhcp = $event->model;

        $dhcp->availabilityZone->nsxService()->put('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id, [
            'json' => [
                'lease_time' => config('defaults.dhcp.lease_time'),
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                    . $dhcp->availabilityZone->nsxService()->getEdgeClusterId(),
                'resource_type' => 'DhcpServerConfig',
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $dhcp->vpc->id
                    ]
                ]
            ]
        ]);

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}

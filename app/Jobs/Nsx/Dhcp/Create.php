<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\TaskJob;
use App\Models\V2\Router;

class Create extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $dhcp = $this->task->resource;

        $management = false;
        if (!empty($this->task->data['router_id'])) {
            $router = Router::find($this->task->data['router_id']);
            $management = ($router) ? $router->is_management : false;
        }

        $dhcp->availabilityZone->nsxService()->put('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id, [
            'json' => [
                'lease_time' => config('defaults.dhcp.lease_time'),
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                    . $dhcp->availabilityZone->getNsxEdgeClusterId($dhcp->vpc->advanced_networking, $management),
                'resource_type' => 'DhcpServerConfig',
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $dhcp->vpc->id
                    ]
                ]
            ]
        ]);
    }
}

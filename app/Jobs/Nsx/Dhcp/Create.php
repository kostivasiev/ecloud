<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Create extends Job
{
    use Batchable, LoggableModelJob;
    
    private Dhcp $model;

    public function __construct(Dhcp $dhcp)
    {
        $this->model = $dhcp;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->model->availabilityZone->nsxService()->put('/policy/api/v1/infra/dhcp-server-configs/' . $this->model->id, [
            'json' => [
                'lease_time' => config('defaults.dhcp.lease_time'),
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                    . $this->model->availabilityZone->getNsxEdgeClusterId($this->model->vpc->advanced_networking),
                'resource_type' => 'DhcpServerConfig',
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $this->model->vpc->id
                    ]
                ]
            ]
        ]);
    }
}

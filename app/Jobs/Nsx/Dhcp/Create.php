<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use GuzzleHttp\Psr7\Response;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Create extends Job
{
    use Batchable;
    
    private Dhcp $dhcp;

    public function __construct(Dhcp $dhcp)
    {
        $this->dhcp = $dhcp;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->dhcp->id]);

        $this->dhcp->availabilityZone->nsxService()->put('/policy/api/v1/infra/dhcp-server-configs/' . $this->dhcp->id, [
            'json' => [
                'lease_time' => config('defaults.dhcp.lease_time'),
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/'
                    . $this->dhcp->availabilityZone->nsxService()->getEdgeClusterId(),
                'resource_type' => 'DhcpServerConfig',
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $this->dhcp->vpc->id
                    ]
                ]
            ]
        ]);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->dhcp->id]);
    }
}

<?php
namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class CreateEndpoint extends Job
{
    use Batchable, LoggableModelJob;

    private Task $task;
    private VpnEndpoint $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $floatingIp = FloatingIp::findOrFail($this->task->data['floating_ip_id']);

        $this->model->vpnService->router->availabilityZone->nsxService()->post(
            '/api/v1/vpn/ipsec/local-endpoints',
            [
                'json' => [
                    'resource_type' => 'IPSecVPNLocalEndpoint',
                    'display_name' => $this->model->id,
                    'local_address' => $floatingIp->ip_address,
                    'local_id' => $floatingIp->ip_address,
                    'ipsec_vpn_service_id' => [
                        'target_id' => $this->model->vpnService->nsx_uuid,
                        'target_type' => 'IPSecVpnService',
                    ],
                    'trust_ca_ids' => [],
                    'trust_crl_ids' => [],
                ]
            ]
        );
    }
}

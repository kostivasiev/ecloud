<?php
namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class CreateEndpoint extends Job
{
    use Batchable, LoggableModelJob;

    private VpnEndpoint $model;

    public function __construct(VpnEndpoint $vpnEndpoint)
    {
        $this->model = $vpnEndpoint;
    }

    public function handle()
    {
        $vpnServiceUuid = $this->getVpnServiceUuid();
        if (!$vpnServiceUuid) {
            throw new \Exception(
                'Create endpoint failed for ' . $this->model->id . ', could not decode vpn service response'
            );
        }

        $this->model->vpnServices()->first()->router->availabilityZone->nsxService()->post(
            '/api/v1/vpn/ipsec/local-endpoints',
            [
                'json' => [
                    'resource_type' => 'IPSecVPNLocalEndpoint',
                    'display_name' => $this->model->id,
                    'local_address' => $this->model->floatingIp->ip_address,
                    'local_id' => $this->model->floatingIp->ip_address,
                    'ipsec_vpn_service_id' => [
                        'target_id' => $vpnServiceUuid,
                        'target_type' => 'IPSecVpnService',
                    ],
                    'trust_ca_ids' => [],
                    'trust_crl_ids' => [],
                ]
            ]
        );
    }

    public function getVpnServiceUuid()
    {
        $vpnService = $this->model->vpnServices()->first();
        // Get VPN Service UUID from NSX
        $response = $vpnService->router->availabilityZone->nsxService()
            ->get(
                '/api/v1/vpn/ipsec/services'
            );
        $vpnServiceData = (json_decode($response->getBody()->getContents()))->results;
        if ($vpnServiceData) {
            foreach ($vpnServiceData as $vpnServiceItem) {
                if ($vpnServiceItem->display_name === $vpnService->id) {
                    return $vpnServiceItem->id;
                }
            }
        }
        return false;
    }
}
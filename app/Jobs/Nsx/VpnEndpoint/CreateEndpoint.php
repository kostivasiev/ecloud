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
        $vpnService = $this->model->vpnServices()->first();
        // Get VPN Service UUID from NSX
        $vpnServiceResponse = $vpnService->router->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/tier-1s/' . $vpnService->router->id .
            '/locale-services/' . $vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnService->id
        );

        $vpnServiceData = json_decode($vpnServiceResponse->getBody()->getContents());
        if (!$vpnServiceData) {
            throw new \Exception(
                'Create endpoint failed for ' . $this->model->id . ', could not decode vpn service response'
            );
        }

        $this->model->vpnServices()->first()->router->availabilityZone->nsxService()->post(
            '/api/v1/vpn/ipsec/local-endpoints',
            [
                'json' => [
                    'resource_type' => 'IPSecVPNLocalEndpoint',
                    'display_name' => 'timtest-vpn-local-endpoint',
                    'local_address' => '203.0.113.76',
                    'local_id' => '203.0.113.76',
                    'ipsec_vpn_service_id' => [
                        'target_id' => '33c25391-7ff7-4e0c-b20d-b4e3ec196921',
                        'target_type' => 'IPSecVpnService',
                    ],
                    'trust_ca_ids' => [],
                    'trust_crl_ids' => [],
                ]
            ]
        );
    }
}
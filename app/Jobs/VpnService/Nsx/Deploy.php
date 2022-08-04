<?php

namespace App\Jobs\VpnService\Nsx;

use App\Jobs\TaskJob;

class Deploy extends TaskJob
{
    /**
     * See: https://network-man0.ecloud-service.ukfast.co.uk/policy/api_includes/method_CreateOrPatchTier1IPSecVpnService.html
     * @return bool|void
     */
    public function handle()
    {
        $vpnService = $this->task->resource;

        $vpnService->router->availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/tier-1s/' . $vpnService->router->id .
            '/locale-services/' . $vpnService->router->id .
            '/ipsec-vpn-services/' . $vpnService->id,
            [
                'json' => [
                    'resource_type' => 'IPSecVpnService',
                    'enabled' => true,
                    'tags' => [
                        [
                            'scope' => config('defaults.tag.scope'),
                            'tag' => $vpnService->router->vpc->id
                        ]
                    ]
                ]
            ]
        );
    }
}

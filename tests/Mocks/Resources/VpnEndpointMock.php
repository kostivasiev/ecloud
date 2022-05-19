<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\FloatingIpResource;
use App\Models\V2\VpnEndpoint;
use Illuminate\Database\Eloquent\Model;

trait VpnEndpointMock
{
    use VpnServiceMock;

    protected $vpnEndpoint;

    public function vpnEndpoint($id = 'vpne-test', $assignFloatingIp = true): VpnEndpoint
    {
        if (!$this->vpnEndpoint) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnEndpoint = VpnEndpoint::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'vpn_service_id' => $this->vpnService()->id,
                ]);
            });

//            if ($assignFloatingIp) {
//                // Assign fIP
//                $floatingIpResource = FloatingIpResource::factory()->assignedTo($this->floatingIp(), $this->vpnEndpoint)->make();
//                $floatingIpResource->id = 'fipr-test';
//                $floatingIpResource->save();
//            }
        }
        return $this->vpnEndpoint;
    }
}
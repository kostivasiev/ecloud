<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VpnSession;
use Illuminate\Database\Eloquent\Model;

trait VpnSessionMock
{
    use VpnServiceMock, VpnEndpointMock, VpnProfileGroupMock;

    protected $vpnSession;

    public function vpnSession($id = 'vpns-test'): VpnSession
    {
        if (!$this->vpnSession) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnSession = factory(VpnSession::class)->create([
                    'id' => $id,
                    'name' => $id,
                    'vpn_profile_group_id' => $this->vpnProfileGroup()->id,
                    'vpn_service_id' => $this->vpnService()->id,
                    'vpn_endpoint_id' => $this->vpnEndpoint()->id
                ]);
            });
        }
        return $this->vpnSession;
    }
}
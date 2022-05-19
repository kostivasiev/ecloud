<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VpnEndpoint;
use Illuminate\Database\Eloquent\Model;

trait VpnEndpointMock
{
    use VpnServiceMock;

    protected $vpnEndpoint;

    public function vpnEndpoint($id = 'vpne-test'): VpnEndpoint
    {
        if (!$this->vpnEndpoint) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnEndpoint = VpnEndpoint::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'vpn_service_id' => $this->vpnService()->id,
                ]);
            });
        }
        return $this->vpnEndpoint;
    }
}
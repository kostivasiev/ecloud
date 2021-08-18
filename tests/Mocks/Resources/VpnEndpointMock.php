<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VpnEndpoint;
use Illuminate\Database\Eloquent\Model;

trait VpnEndpointMock
{
    protected $vpnEndpoint;

    public function vpnEndpoint($id = 'vpne-test', $assignFloatingIp = true): VpnEndpoint
    {
        if (!$this->vpnEndpoint) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnEndpoint = factory(VpnEndpoint::class)->create([
                    'id' => $id,
                    'name' => $id,
                    'vpn_service_id' => $this->vpnService()->id,
                ]);
            });

            if ($assignFloatingIp) {
                $this->vpnEndpoint->floatingIp()->save($this->floatingIp());
            }
        }
        return $this->vpnEndpoint;
    }
}
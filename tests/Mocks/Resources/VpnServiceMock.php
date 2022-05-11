<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VpnService;
use Illuminate\Database\Eloquent\Model;

trait VpnServiceMock
{
    protected $vpnService;

    public function vpnService($id = 'vpn-test'): VpnService
    {
        if (!$this->vpnService) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnService = VpnService::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'router_id' => $this->router()->id,
                ]);
            });
        }
        return $this->vpnService;
    }
}
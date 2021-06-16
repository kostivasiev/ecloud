<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnSession $vpnSession;
    protected FloatingIp $floatingIp;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create([
            'floating_ip_id' => $this->floatingIp->id,
        ]);
        $this->vpnSession = factory(VpnSession::class)->create(
            [
                'name' => '',
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '127.1.1.1/32',
                'local_networks' => '127.1.1.1/32,127.1.10.1/24',
            ]
        );
        $this->vpnSession->vpnServices()->attach($this->vpnService);
        $this->vpnSession->vpnEndpoints()->attach($this->vpnEndpoint);
    }

    public function testUpdateResource()
    {
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            []
        );
        dd(
            $this->response->getStatusCode(),
            json_decode($this->response->getContent(), true)
        );
    }
}
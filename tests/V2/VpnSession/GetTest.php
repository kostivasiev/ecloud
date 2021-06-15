<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
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
            'fip_id' => $this->floatingIp->id,
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

    public function testGetCollection()
    {
        $this->get('/v2/vpn-sessions')
            ->seeJson(
                [
                    'id' => $this->vpnSession->id,
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id)
            ->seeJson(
                [
                    'id' => $this->vpnSession->id,
                ]
            )->assertResponseStatus(200);
    }

    public function testGetEndpointsCollection()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/endpoints')
            ->seeJson(
                [
                    'id' => $this->vpnEndpoint->id,
                    'name' => $this->vpnEndpoint->name,
                ]
            )->assertResponseStatus(200);
    }

    public function testGetServicesCollection()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/services')
            ->seeJson(
                [
                    'id' => $this->vpnService->id,
                    'name' => $this->vpnService->name,
                ]
            )->assertResponseStatus(200);
    }
}
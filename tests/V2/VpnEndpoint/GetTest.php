<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected VpnEndpoint $vpnEndpoint;
    protected VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $floatingIp = FloatingIp::withoutEvents(function () {
            return FloatingIp::factory()->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = VpnEndpoint::factory()->create(
            [
                'name' => 'Get Test',
                'vpn_service_id' => $this->vpnService->id,
            ]
        );
        $floatingIp->resource()->associate($this->vpnEndpoint);
        $floatingIp->save();
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-endpoints')
            ->assertJsonFragment(
                [
                    'id' => $this->vpnEndpoint->id,
                ]
            )->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-endpoints/' . $this->vpnEndpoint->id)
            ->assertJsonFragment(
                [
                    'id' => $this->vpnEndpoint->id,
                ]
            )->assertStatus(200);
    }

    public function testGetResourceWrongUser()
    {
        $this->be(new Consumer(999, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/vpn-endpoints/' . $this->vpnEndpoint->id)
            ->assertJsonFragment(
                [
                    'title' => 'Not found',
                    'detail' => 'No Vpn Endpoint with that ID was found',
                ]
            )->assertStatus(404);
    }
}
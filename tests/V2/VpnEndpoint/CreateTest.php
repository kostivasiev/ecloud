<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testCreateResource()
    {
        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
            'floating_ip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->assertResponseStatus(202);
    }

    public function testCreateResourceVpnAlreadyUsed()
    {
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-aaa111bbb',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.2',
            ]);
        });
        factory(VpnEndpoint::class, 1)->create([
            'name' => 'Original Endpoint',
            'floating_ip_id' => $floatingIp->id,
        ])->each(function ($endpoint) {
            $endpoint->vpnServices()->attach($this->vpnService->id);
            $endpoint->save();
        });
        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
            'floating_ip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A vpn endpoint already exists for the specified vpn service id',
                ]
            )
            ->assertResponseStatus(422);
    }

    public function testCreateResourceFipInUse()
    {
        $vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        factory(VpnEndpoint::class, 1)->create([
            'name' => 'Original Endpoint',
            'floating_ip_id' => $this->floatingIp->id,
        ])->each(function ($endpoint) use ($vpnService) {
            $endpoint->vpnServices()->attach($vpnService->id);
            $endpoint->save();
        });
        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
            'floating_ip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A vpn endpoint already exists for the specified floating ip id',
                ]
            )
            ->assertResponseStatus(422);
    }

    public function testCreateWithoutFloatingIp()
    {
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-aaa111bbb',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.2',
            ]);
        });
        app()->bind(FloatingIp::class, function () use ($floatingIp) {
            return $floatingIp;
        });

        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->assertResponseStatus(202);

        $id = json_decode($this->response->getContent())->data->id;
        $vpnEndpoint = VpnEndpoint::findOrFail($id);
        $this->assertEquals($floatingIp->id, $vpnEndpoint->floating_ip_id);
    }
}
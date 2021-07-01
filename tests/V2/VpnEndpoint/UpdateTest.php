<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnEndpoint $vpnEndpoint;
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
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create(
            [
                'name' => 'Update Test',
                'floating_ip_id' => $this->floatingIp->id,
            ]
        );
        $this->vpnEndpoint->vpnServices()->attach($this->vpnService->id);
        $this->vpnEndpoint->save();
    }

    public function testUpdateResource()
    {
        $data = [
            'name' => 'Updated name',
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->seeInDatabase(
                'vpn_endpoints',
                [
                    'name' => $data['name']
                ],
                'ecloud'
            )
            ->assertResponseStatus(202);
    }

    public function testUpdateResourceWithSameData()
    {
        $data = [
            'name' => $this->vpnEndpoint->name,
            'floating_ip_id' => $this->vpnEndpoint->floating_ip_id,
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->assertResponseStatus(202);
    }

    public function testUpdateWithDataThatIsAlreadyInUse()
    {
        // Create VPN
        $vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        // Create Floating Ip
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-aaa111aaa',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.5',
            ]);
        });
        // Create Local Endpoint
        factory(VpnEndpoint::class, 1)->create(
            [
                'name' => 'Other LE Test',
                'floating_ip_id' => $floatingIp->id,
            ]
        )->each(function ($endpoint) use ($vpnService) {
            $endpoint->vpnServices()->attach($vpnService->id);
            $endpoint->save();
        });
        // Update original local endpoint
        $data = [
            'floating_ip_id' => $floatingIp->id,
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The floating ip id has already been taken',
                    'source' => 'floating_ip_id',
                ]
            )
            ->assertResponseStatus(422);
    }
}

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
                'vpn_service_id' => $this->vpnService->id,
            ]
        );
        $this->floatingIp->resource()->associate($this->vpnEndpoint);
        $this->floatingIp->save();
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
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->assertResponseStatus(202);
    }
}

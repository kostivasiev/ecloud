<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\Vpn;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnEndpoint $vpnEndpoint;
    protected FloatingIp $floatingIp;
    protected Vpn $vpn;

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
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create(
            [
                'name' => 'Update Test',
                'vpn_id' => $this->vpn->id,
                'fip_id' => $this->floatingIp->id,
            ]
        );
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
            'vpn_id' => $this->vpnEndpoint->vpn_id,
            'fip_id' => $this->vpnEndpoint->fip_id,
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->assertResponseStatus(202);
    }

    public function testUpdateWithDataThatIsAlreadyInUse()
    {
        // Create VPN
        $vpn = factory(Vpn::class)->create([
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
        factory(VpnEndpoint::class)->create(
            [
                'name' => 'Other LE Test',
                'vpn_id' => $vpn->id,
                'fip_id' => $floatingIp->id,
            ]
        );
        // Update original local endpoint
        $data = [
            'vpn_id' => $vpn->id,
            'fip_id' => $floatingIp->id,
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A vpn endpoint already exists for the specified vpn id',
                    'source' => 'vpn_id',
                ]
            )
            ->seeJson(
                [
                    'title' => 'Validation Error',
                    'detail' => 'A vpn endpoint already exists for the specified fip id',
                    'source' => 'fip_id',
                ]
            )
            ->assertResponseStatus(422);
    }
}

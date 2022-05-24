<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Illuminate\Database\Eloquent\Model;
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
        $floatingIp->save();
        $this->assignFloatingIp($this->floatingIp(), $this->vpnEndpoint);
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

    public function testFilterByVpcId()
    {
        // Create another endpoint with a different vpc_id
        $vpc = Model::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-11111111',
                'region_id' => $this->region()->id
            ]);
        });
        $router = Model::withoutEvents(function () use ($vpc) {
            return Router::factory()->create([
                'id' => 'rtr-11111111',
                'vpc_id' => $vpc->id,
            ]);
        });
        $floatingIp = Model::withoutEvents(function () use ($vpc) {
            return FloatingIp::factory()->create([
                'id' => 'fip-11111111',
                'vpc_id' => $vpc->id,
                'ip_address' => '203.0.111.1',
            ]);
        });
        $vpnService = VpnService::factory()->create([
            'router_id' => $router->id,
        ]);
        $vpcEndpoint = VpnEndpoint::factory()->create([
            'vpn_service_id' => $vpnService->id,
        ]);
        $floatingIp->resource()->associate($vpcEndpoint);
        $floatingIp->save();

        // eq
        $this->get('/v2/vpn-endpoints?vpc_id:eq=' . $vpc->id)
            ->assertJsonFragment([
                'vpc_id' => $vpc->id,
            ])->assertJsonFragment([
                'count' => 1
            ])->assertStatus(200);

        // neq
        $this->get('/v2/vpn-endpoints?vpc_id:neq=' . $vpc->id)
            ->assertJsonFragment([
                'vpc_id' => $this->vpc()->id,
            ])->assertJsonFragment([
                'count' => 1
            ])->assertStatus(200);
    }
}
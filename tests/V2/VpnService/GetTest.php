<?php

namespace Tests\V2\VpnService;

use App\Models\V2\FloatingIp;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\VpnService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $vpn;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpn = VpnService::factory()->create([
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/vpn-services',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->vpn->id,
                'router_id' => $this->vpn->router_id,
                'vpc_id' => $this->vpn->router->vpc->id,
            ])
            ->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/vpn-services/' . $this->vpn->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->vpn->id,
                'router_id' => $this->vpn->router_id,
                'vpc_id' => $this->vpn->router->vpc->id,
            ])
            ->assertStatus(200);
    }

    public function testVpcIdFiltering()
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
        VpnService::factory()->create([
            'router_id' => $router->id,
        ]);

        // eq
        $this->asAdmin()
            ->get('/v2/vpn-services?vpc_id:eq=' . $vpc->id)
            ->assertJsonFragment([
                'vpc_id' => $vpc->id,
            ])->assertJsonFragment([
                'count' => 1
            ])->assertStatus(200);

        // neq
        $this->get('/v2/vpn-services?vpc_id:neq=' . $vpc->id)
            ->assertJsonFragment([
                'vpc_id' => $this->vpc()->id,
            ])->assertJsonFragment([
                'count' => 1
            ])->assertStatus(200);
    }
}

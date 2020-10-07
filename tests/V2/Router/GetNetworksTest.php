<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallRule;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetNetworksTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;
    protected Vpn $vpn;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router->id,
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/routers/'.$this->router->getKey().'/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'        => $this->network->getKey(),
                'name'      => $this->network->name,
                'router_id' => $this->network->router_id,
            ])
            ->assertResponseStatus(200);
    }

}

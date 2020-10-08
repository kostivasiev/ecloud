<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpnsTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
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
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/routers/'.$this->router->getKey().'/vpns',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $this->vpn->getKey(),
                'router_id'            => $this->vpn->router_id,
                'availability_zone_id' => $this->vpn->availability_zone_id,
            ])
            ->assertResponseStatus(200);
    }

}

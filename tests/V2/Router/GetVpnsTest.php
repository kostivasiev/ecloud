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
    protected \Faker\Generator $faker;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;
    protected Vpn $vpn;
    protected AvailabilityZone $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $this->vpn = factory(Vpn::class)->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/routers/'.$this->router->id.'/vpns',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $this->vpn->id,
                'router_id'            => $this->vpn->router_id,
            ])
            ->assertResponseStatus(200);
    }

}

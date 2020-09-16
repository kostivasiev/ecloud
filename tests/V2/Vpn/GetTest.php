<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $region;
    protected $availability_zone;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/vpns',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $this->get(
            '/v2/vpns',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $vpn->id,
                'router_id'            => $vpn->router_id,
                'availability_zone_id' => $vpn->availability_zone_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
        ]);
        $this->get(
            '/v2/vpns/' . $vpn->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'                   => $vpn->id,
                'router_id'            => $vpn->router_id,
                'availability_zone_id' => $vpn->availability_zone_id,
            ])
            ->assertResponseStatus(200);
    }
}

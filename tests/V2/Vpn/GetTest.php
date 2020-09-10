<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
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
        $router = factory(Router::class)->create();
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
        $router = factory(Router::class)->create();
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

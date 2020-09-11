<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
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
        $router = factory(Router::class)->create();
        $data = [
            'router_id' => $router->id,
        ];
        $this->post(
            '/v2/vpns',
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullRouterIdIsFailed()
    {
        $this->post('/v2/vpns', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Validation Error',
            'detail' => 'The router id field is required',
            'status' => 422,
            'source' => 'router_id'
        ])->assertResponseStatus(422);
    }

    public function testNotUserOwnedRouterIdIsFailed()
    {
        $router = factory(Router::class)->create();
        $zone = factory(AvailabilityZone::class)->create();

        $data = [
            'router_id' => $router->id,
            'availability_zone_id' => $zone->id,
        ];
        $this->post(
            '/v2/vpns',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $router = factory(Router::class)->create();
        $this->post('/v2/vpns', [
            'router_id' => $router->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(201);
        $vpnId = (json_decode($this->response->getContent()))->data->id;
        $vpnItem = Vpn::findOrFail($vpnId);
        $this->assertEquals($vpnItem->router_id, $router->id);
    }
}

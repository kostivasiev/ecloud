<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
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
        $availabilityZone = factory(AvailabilityZone::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ]);
        $data = [
            'router_id'            => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
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

    public function testNullRouterIdIsDenied()
    {
        $router = factory(Router::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ]);
        $data = [
            'router_id'            => '',
            'availability_zone_id' => $availabilityZone->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The router id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullAvailabilityZoneIdIsDenied()
    {
        $router = factory(Router::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ]);
        $data = [
            'router_id'            => $router->id,
            'availability_zone_id' => '',
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The availability zone id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $router = factory(Router::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create();
        $vpn = factory(Vpn::class)->create([
            'router_id' => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ]);
        $data = [
            'router_id'            => $router->id,
            'availability_zone_id' => $availabilityZone->id,
        ];
        $this->patch(
            '/v2/vpns/' . $vpn->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);

        $vpnItem = Vpn::findOrFail($vpn->getKey());
        $this->assertEquals($data['router_id'], $vpnItem->router_id);
        $this->assertEquals($data['availability_zone_id'], $vpnItem->availability_zone_id);
    }
}

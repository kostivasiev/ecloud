<?php

namespace Tests\V2\GatewayRouter;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use App\Models\V2\Router;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class RelationshipTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNotAdminIsDenied()
    {
        $router = factory(Router::class)->create();
        $gateway = factory(Gateway::class)->create();
        $this->put(
            '/v2/routers/' . $router->getKey() . '/gateways/' . $gateway->id,
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testInvalidRouterFails()
    {
        $gateway = factory(Gateway::class)->create();
        $this->put(
            '/v2/routers/' . $this->faker->uuid . '/gateways/' . $gateway->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Router with that ID was found',
            ])
            ->assertResponseStatus(404);
    }

    public function testInvalidGatewayFails()
    {
        $router = factory(Router::class)->create();
        $this->put('/v2/routers/' . $router->id . '/gateways/' . $this->faker->uuid, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups'    => 'ecloud.write',
        ])->seeJson([
            'title'  => 'Not found',
            'detail' => 'No Gateway with that ID was found',
        ])->assertResponseStatus(404);
    }

    public function testCreateValidAssociation()
    {
        $availabilityZone = factory(AvailabilityZone::class)->create();
        $router = factory(Router::class)->create();
        $gateway = factory(Gateway::class)->create([
            'availability_zone_id' => $availabilityZone->getKey()
        ]);
        $this->put(
        '/v2/routers/' . $router->id . '/gateways/' . $gateway->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups'    => 'ecloud.write',
        ])->assertResponseStatus(204);

        $associated = $router->gateways()->first();
        $this->assertEquals($associated->getKey(), $gateway->getKey());
    }

    public function testRemoveAssociation()
    {
        $router = factory(Router::class)->create();
        $gateway = factory(Gateway::class)->create();
        $router->gateways()->attach($gateway->id);
        $this->delete(
            '/v2/routers/' . $router->id . '/gateways/' . $gateway->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $router->refresh();
        $this->assertEquals(0, $router->gateways()->count());
    }

}

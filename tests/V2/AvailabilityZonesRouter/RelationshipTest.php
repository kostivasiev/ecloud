<?php

namespace Tests\V2\AvailabilityZonesRouter;

use App\Models\V2\AvailabilityZones;
use App\Models\V2\Routers;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

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
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $router->getKey(),
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

    public function testInvalidAvailabilityZoneFails()
    {
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/availability-zones/' . $this->faker->uuid . '/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Availability Zones with that ID was found',
            ])
            ->assertResponseStatus(404);
    }

    public function testInvalidRouterFails()
    {
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Routers with that ID was found',
            ])
            ->assertResponseStatus(404);
    }

    public function testCreateValidAssociation()
    {
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);

        // test that the association has occurred
        $router->refresh();
        $associated = $availabilityZones->routers()->first();
        $this->assertEquals($associated->toArray(), $router->toArray());
    }

    public function testRemoveAssociation()
    {
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $availabilityZones->routers()->attach($router->getKey());
        $this->delete(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $router->refresh();
        $this->assertEquals(0, $availabilityZones->routers()->count());
    }

}

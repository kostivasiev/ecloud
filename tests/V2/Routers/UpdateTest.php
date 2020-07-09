<?php

namespace Tests\V2\Routers;

use App\Models\V2\Routers;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNonAdminIsDenied()
    {
        $zone = $this->createRouter();
        $data = [
            'name'       => 'Manchester Router 2',
            'gateway_id' => $this->faker->uuid,
        ];
        $this->patch(
            '/v2/routers/' . $zone->getKey(),
            $data,
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

    public function testNullNameIsDenied()
    {
        $zone = $this->createRouter();
        $data = [
            'name'       => '',
            'gateway_id' => $this->faker->uuid,
        ];
        $this->patch(
            '/v2/routers/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullGatewayIdIsDenied()
    {
        $zone = $this->createRouter();
        $data = [
            'name'       => 'Manchester Router 2',
            'gateway_id' => '',
        ];
        $this->patch(
            '/v2/routers/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The gateway id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'gateway_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $zone = $this->createRouter();
        $data = [
            'name'       => 'Manchester Router 2',
            'gateway_id' => $this->faker->uuid,
        ];
        $this->patch(
            '/v2/routers/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $routerItem = Routers::findOrFail($zone->getKey());
        $this->assertEquals($data['name'], $routerItem->name);
        $this->assertEquals($data['gateway_id'], $routerItem->gateway_id);
    }

    /**
     * Create Router
     * @return \App\Models\V2\Routers
     */
    public function createRouter(): Routers
    {
        $router = factory(Routers::class, 1)->create()->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}
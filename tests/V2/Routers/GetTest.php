<?php

namespace Tests\V2\Routers;

use App\Models\V2\Routers;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
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
        $this->get(
            '/v2/routers',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
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
        $routerItem = factory(Routers::class, 1)->create([
            'name'       => 'Manchester Router 1',
        ])->first();
        $this->get(
            '/v2/routers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $routerItem->id,
                'name'       => $routerItem->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $router = factory(Routers::class, 1)->create([
            'name'       => 'Manchester Router 1',
        ])->first();
        $router->save();
        $router->refresh();

        $this->get(
            '/v2/routers/' . $router->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $router->id,
                'name'       => $router->name,
            ])
            ->assertResponseStatus(200);
    }

}

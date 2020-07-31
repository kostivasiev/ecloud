<?php

namespace Tests\V2\Router;

use App\Models\V2\Router;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/routers/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Router with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $router = factory(Router::class, 1)->create()->first();
        $router->refresh();
        $this->delete(
            '/v2/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $routerItem = Router::withTrashed()->findOrFail($router->getKey());
        $this->assertNotNull($routerItem->deleted_at);
    }

}

<?php

namespace Tests\V2\Router;

use App\Models\V2\Router;
use App\Models\V2\Vpc;
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

        $this->vpc = factory(Vpc::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
    }

    public function testNonAdminIsDenied()
    {
        $router = $this->createRouter();
        $data = [
            'name' => 'Manchester Router 2',
            'vpc_id' => $this->vpc->getKey()
        ];
        $this->patch(
            '/v2/routers/' . $router->getKey(),
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
        $router = $this->createRouter();
        $data = [
            'name'       => '',
            'vpc_id' => $this->vpc->getKey()
        ];
        $this->patch(
            '/v2/routers/' . $router->getKey(),
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

    public function testInvalidVpcIdIdIsFailed()
    {
        $data = [
            'name'    => 'Manchester Router 2',
            'vpc_id' => $this->faker->uuid()
        ];

        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $router = $this->createRouter();
        $data = [
            'name'       => 'Manchester Router 2',
            'vpc_id' => $this->vpc->getKey()
        ];
        $this->patch(
            '/v2/routers/' . $router->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $routerItem = Router::findOrFail($router->getKey());
        $this->assertEquals($data['name'], $routerItem->name);
    }

    /**
     * Create Router
     * @return \App\Models\V2\Router
     */
    public function createRouter(): Router
    {
        $router = factory(Router::class, 1)->create([
            'vpc_id' => $this->vpc->getKey()
        ])->first();
        $router->save();
        $router->refresh();
        return $router;
    }

}

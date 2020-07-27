<?php

namespace Tests\V2\Router;

use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
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

        $this->router = factory(Router::class, 1)->create([
            'name'       => 'Manchester Router 1',
            'vpc_id' => $this->vpc->getKey()
        ])->first();
    }

    public function testNonAdminIsDenied()
    {
        $data = [
            'name'    => 'Manchester Router 1',
            'vpc_id'    => $this->vpc->getKey(),
        ];
        $this->post(
            '/v2/routers',
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

    public function testNullNameIsFailed()
    {
        $data = [
            'name' => '',
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
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name'    => 'Manchester Network',
            'vpc_id' => $this->faker->uuid(),
        ];

        $this->patch(
            '/v2/routers/' . $this->router->getKey(),
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

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => 'Manchester Router 1',
            'vpc_id'    => $this->vpc->getKey()
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $routerId = (json_decode($this->response->getContent()))->data->id;
        $router = Router::find($routerId);
        $this->assertNotNull($router);
    }

}

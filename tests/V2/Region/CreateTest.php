<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create([
            'name'    => 'Manchester',
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'name'    => 'United Kingdom',
        ];
        $this->post(
            '/v2/regions',
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

    public function testNullNameIsFailed()
    {
        $data = [
            'name'    => '',
        ];
        $this->post(
            '/v2/regions',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
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

    public function testNotAdminFails()
    {
        $data = [
            'name'    => $this->faker->word(),
        ];
        $this->post(
            '/v2/regions',
            $data,
            [
                'X-consumer-custom-id' => '1-0',
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

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => $this->faker->word(),
        ];
        $this->post(
            '/v2/regions',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )
            ->seeInDatabase(
                'regions',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(201);
    }
}

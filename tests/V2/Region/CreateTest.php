<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'name' => 'United Kingdom',
        ];
        $this->post(
            '/v2/regions',
            $data,
            []
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testNullNameIsFailed()
    {
        $data = [
            'name' => '',
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
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertStatus(422);
    }

    public function testNotAdminFails()
    {
        $data = [
            'name' => $this->faker->word(),
        ];
        $this->post(
            '/v2/regions',
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => $this->faker->word(),
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
            ->assertStatus(201);
        $this->assertDatabaseHas('regions', $data, 'ecloud');
    }
}

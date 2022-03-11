<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $faker;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = Region::factory()->create();
    }

    public function testNotAdminIsDenied()
    {
        $data = [
            'name' => 'United Kingdom',
        ];

        $this->patch(
            '/v2/regions/' . $this->region->id,
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $data = [
            'name' => '',
        ];
        $this->patch(
            '/v2/regions/' . $this->region->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The name field, when specified, cannot be null',
            'status' => 422,
            'source' => 'name'
        ])->assertStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            'name' => $this->faker->word(),
        ];
        $this->patch(
            '/v2/regions/' . $this->region->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(200);
        $this->assertDatabaseHas('regions', $data, 'ecloud');

        $region = Region::findOrFail($this->region->id);
        $this->assertEquals($data['name'], $region->name);
    }
}

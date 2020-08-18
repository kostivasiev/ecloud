<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
    }

    public function testNotAdminIsDenied()
    {
        $data = [
            'name'    => 'United Kingdom',
        ];

        $this->patch(
            '/v2/regions/' . $this->region->getKey(),
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

    public function testNullNameIsDenied()
    {
        $data = [
            'name'    => '',
        ];
        $this->patch(
            '/v2/regions/' . $this->region->getKey(),
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

    public function testValidDataIsSuccessful()
    {
        $data = [
            'name'    => $this->faker->word(),
        ];
        $this->patch(
            '/v2/regions/' . $this->region->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'regions',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(200);

        $region = Region::findOrFail($this->region->getKey());
        $this->assertEquals($data['name'], $region->name);
    }
}

<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
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
        $this->delete(
            '/v2/regions/' . $this->region->id,
            [],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write'
            ]
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/regions/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Region with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/regions/' . $this->region->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $region = Region::withTrashed()->findOrFail($this->region->id);
        $this->assertNotNull($region->deleted_at);
    }

}

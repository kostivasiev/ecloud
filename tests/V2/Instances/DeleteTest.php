<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Networks;
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
        $this->network = factory(Networks::class, 1)->create([
            'name' => 'Manchester Network',
        ])->first();
        $this->instance = factory(Instance::class, 1)->create([
            'network_id' => $this->network->getKey(),
        ])->first();
    }

    public function testNoPermsIsDenied()
    {
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/instances/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Instance with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance->getKey());
        $this->assertNotNull($instance->deleted_at);
    }
}

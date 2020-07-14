<?php

namespace Tests\V2\Networks;

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
    }

    public function testNoPermsIsDenied()
    {
        $net = factory(Networks::class, 1)->create()->first();
        $net->refresh();
        $this->delete(
            '/v2/networks/' . $net->getKey(),
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
            '/v2/networks/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Networks with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $net = factory(Networks::class, 1)->create()->first();
        $net->refresh();
        $this->delete(
            '/v2/networks/' . $net->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $network = Networks::withTrashed()->findOrFail($net->getKey());
        $this->assertNotNull($network->deleted_at);
    }

}

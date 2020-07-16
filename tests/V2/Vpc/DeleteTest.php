<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
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
        $vdc = factory(Vpc::class, 1)->create()->first();
        $vdc->refresh();
        $this->delete(
            '/v2/vpcs/' . $vdc->getKey(),
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
            '/v2/vpcs/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Vpc with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $vdc = factory(Vpc::class, 1)->create()->first();
        $vdc->refresh();
        $this->delete(
            '/v2/vpcs/' . $vdc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $virtualPrivateCloud = Vpc::withTrashed()->findOrFail($vdc->getKey());
        $this->assertNotNull($virtualPrivateCloud->deleted_at);
    }

}

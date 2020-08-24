<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
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
        $vpc = factory(Vpc::class)->create();
        $this->delete(
            '/v2/vpcs/' . $vpc->getKey(),
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

    public function testNonMatchingResellerIdFails()
    {
        $vpc = factory(Vpc::class)->create(['reseller_id' => 3]);
        $this->delete(
            '/v2/vpcs/' . $vpc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '1-0',
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
        $this->region = factory(Region::class)->create();
        $vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $vpc->refresh();
        $this->delete(
            '/v2/vpcs/' . $vpc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $virtualPrivateCloud = Vpc::withTrashed()->findOrFail($vpc->getKey());
        $this->assertNotNull($virtualPrivateCloud->deleted_at);
    }

}

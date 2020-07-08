<?php

namespace Tests\V2\VirtualDataCentres;

use App\Models\V2\VirtualDataCentres;
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
        $vdc = factory(VirtualDataCentres::class, 1)->create()->first();
        $vdc->refresh();
        $this->delete(
            '/v2/vdcs/' . $vdc->getKey(),
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
            '/v2/vdcs/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Virtual Data Centres with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $vdc = factory(VirtualDataCentres::class, 1)->create()->first();
        $vdc->refresh();
        $this->delete(
            '/v2/vdcs/' . $vdc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $virtualDataCentre = VirtualDataCentres::withTrashed()->findOrFail($vdc->getKey());
        $this->assertNotNull($virtualDataCentre->deleted_at);
    }

}

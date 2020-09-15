<?php

namespace Tests\V2\Volume;

use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    /** @var Volume */
    private $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);

    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/volumes/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Volume with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/volumes/' . $this->volume->getKey(),
            [],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $resource = Volume::withTrashed()->findOrFail($this->volume->getKey());
        $this->assertNotNull($resource->deleted_at);
    }
}

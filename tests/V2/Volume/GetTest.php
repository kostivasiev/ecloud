<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $vpc;
    protected $region;
    protected $availabilityZone;
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester DC',
            'region_id' => $this->region->id
        ]);

        $this->volume = factory(Volume::class)->create([
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/volumes',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->volume->id,
                'name' => $this->volume->name,
                'vpc_id' => $this->volume->vpc_id,
                'availability_zone_id' => $this->volume->availability_zone_id,
                'capacity' => $this->volume->capacity,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/volumes/' . $this->volume->id,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->volume->id,
                'name' => $this->volume->name,
                'vpc_id' => $this->volume->vpc_id,
                'availability_zone_id' => $this->volume->availability_zone_id
            ])
            ->dontSeeJson(
                [
                    'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
                ]
            )
            ->assertResponseStatus(200);
    }

    public function testGetItemDetailAdmin()
    {
        $this->get(
            '/v2/volumes/' . $this->volume->id,
            [
                'X-consumer-custom-id' => 'o-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->volume->id,
                'name' => $this->volume->name,
                'vpc_id' => $this->volume->vpc_id,
                'availability_zone_id' => $this->volume->availability_zone_id,
                'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
            ])
            ->assertResponseStatus(200);
    }

}

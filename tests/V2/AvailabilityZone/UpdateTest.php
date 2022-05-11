<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $faker;

    protected $availabilityZone;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $region->id
        ]);
        $this->region = Region::factory()->create();
    }

    public function testValidDataIsSuccessful()
    {
        $patch = $this->patch(
            '/v2/availability-zones/' . $this->availabilityZone->id,
            [
                'code' => 'MAN2',
                'name' => 'Manchester Zone 2',
                'datacentre_site_id' => 2,
                'region_id' => $this->region->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(200);

        $this->assertDatabaseHas(
            'availability_zones',
            [
                'id' => $this->availabilityZone->id,
                'code' => 'MAN2',
                'name' => 'Manchester Zone 2',
                'datacentre_site_id' => 2,
                'region_id' => $this->region->id,
            ],
            'ecloud'
        );

        // Check for single occurence of id in the meta location
        $availabilityZoneId
            = (json_decode($patch->getContent()))->data->id;
        $metaLocation
            = (json_decode($patch->getContent()))->meta->location;
        $this->assertTrue((substr_count($metaLocation, $availabilityZoneId)
            == 1));
    }
}

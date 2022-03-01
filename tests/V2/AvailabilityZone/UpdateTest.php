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
        $region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ]);
        $this->region = factory(Region::class)->create();
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch(
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
        )
            ->seeInDatabase(
                'availability_zones',
                [
                    'id' => $this->availabilityZone->id,
                    'code' => 'MAN2',
                    'name' => 'Manchester Zone 2',
                    'datacentre_site_id' => 2,
                    'region_id' => $this->region->id,
                ],
                'ecloud'
            )
            ->assertResponseStatus(200);

        // Check for single occurence of id in the meta location
        $availabilityZoneId
            = (json_decode($this->response->getContent()))->data->id;
        $metaLocation
            = (json_decode($this->response->getContent()))->meta->location;
        $this->assertTrue((substr_count($metaLocation, $availabilityZoneId)
            == 1));
    }
}

<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Database\Seeders\ResourceTierSeeder;
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
            'region_id' => $region->id,
            'resource_tier_id' => 'test-resource-tier'
        ]);
        $this->region = Region::factory()->create();
        (new ResourceTierSeeder())->run();
    }

    public function testValidDataIsSuccessful()
    {
        $this->assertEquals('test-resource-tier', $this->availabilityZone->default_resource_tier_id);

        $patch = $this->asAdmin()->patch(
            '/v2/availability-zones/' . $this->availabilityZone->id,
            [
                'code' => 'MAN2',
                'name' => 'Manchester Zone 2',
                'datacentre_site_id' => 2,
                'region_id' => $this->region->id,
                'resource_tier_id' => 'rt-aaaaaaaa',
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
                'resource_tier_id' => 'rt-aaaaaaaa',
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

        $this->assertEquals('rt-aaaaaaaa', $this->availabilityZone->refresh()->default_resource_tier_id);
    }
}

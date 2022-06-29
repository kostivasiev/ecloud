<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\ResourceTier;
use Database\Seeders\ResourceTierSeeder;
use Faker\Factory as Faker;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNonAdminIsDenied()
    {
        $data = [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'region_id' => $this->region()->id,
            'resource_tier_id' => 'rt-aaaaaaaa',
        ];
        $this->asUser()->post('/v2/availability-zones', $data)
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testNullCodeIsFailed()
    {
        $data = [
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'region_id' => $this->region()->id,
            'resource_tier_id' => 'rt-aaaaaaaa',
            ];
        $this->asAdmin()->post('/v2/availability-zones', $data)
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The code field is required',
                'status' => 422,
                'source' => 'code'
            ])
            ->assertStatus(422);
    }

    public function testNullNameIsFailed()
    {
        $data = [
            'code' => 'MAN1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'region_id' => $this->region()->id,
            'resource_tier_id' => 'rt-aaaaaaaa',
            ];
        $this->asAdmin()->post('/v2/availability-zones', $data)
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertStatus(422);
    }

    public function testNullSiteIdIsFailed()
    {
        $data = [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'region_id' => $this->region()->id,
            'resource_tier_id' => 'rt-aaaaaaaa',
        ];
        $this->asAdmin()->post('/v2/availability-zones', $data)
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The datacentre site id field is required',
                'status' => 422,
                'source' => 'datacentre_site_id'
            ])
            ->assertStatus(422);
    }

    public function testNullRegionIdIsFailed()
    {
        $data = [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'region_id' => '',
            'resource_tier_id' => 'rt-aaaaaaaa',
        ];
        $this->asAdmin()->post('/v2/availability-zones', $data)
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The region id field is required',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertStatus(422);
    }

    public function testValidDataSucceeds()
    {
        ResourceTier::factory()->create([
            'id' => 'rt-aaaaaaaa',
            'name' => 'Standard CPU',
            'availability_zone_id' => 'az-aaaaaaaa'
        ]);

        $data = [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'is_public' => false,
            'region_id' => $this->region()->id,
            'resource_tier_id' => 'rt-aaaaaaaa',
        ];
        $post = $this->asAdmin()->post('/v2/availability-zones', $data)
            ->assertStatus(201);

        $availabilityZoneId = (json_decode($post->getContent()))->data->id;
        $metaLocation = (json_decode($post->getContent()))->meta->location;
        $post->assertJsonFragment([
            'id' => $availabilityZoneId,
        ]);

        // Check that the id is in the returned meta location
        $this->assertTrue((substr_count($metaLocation, $availabilityZoneId) == 1));

        $resource = AvailabilityZone::findOrFail($availabilityZoneId);
        $this->assertFalse($resource->is_public);

        $this->assertDatabaseHas('availability_zones', $data, 'ecloud');
    }

}

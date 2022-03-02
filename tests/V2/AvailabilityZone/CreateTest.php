<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
            'region_id' => ''
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
        $data = [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'is_public' => false,
            'region_id' => $this->region()->id
        ];
        $post = $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
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
    }

}

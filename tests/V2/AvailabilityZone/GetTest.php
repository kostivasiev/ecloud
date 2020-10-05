<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZone->id,
                'code' => $this->availabilityZone->code,
                'name' => $this->availabilityZone->name,
                'datacentre_site_id' => $this->availabilityZone->datacentre_site_id,
                'region_id' => $this->availabilityZone->region_id
            ])
            ->assertResponseStatus(200);
    }

    public function testGetCollectionNonAdminPropertiesHidden()
    {
        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZone->id,
                'code' => $this->availabilityZone->code,
                'name' => $this->availabilityZone->name,
                'datacentre_site_id' => $this->availabilityZone->datacentre_site_id,
                'region_id' => $this->availabilityZone->region_id
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZone->id,
                'code' => $this->availabilityZone->code,
                'name' => $this->availabilityZone->name,
                'datacentre_site_id' => $this->availabilityZone->datacentre_site_id,
                'region_id' => $this->availabilityZone->region_id
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetailNonAdminPropertiesHidden()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZone->id,
                'code' => $this->availabilityZone->code,
                'name' => $this->availabilityZone->name,
                'datacentre_site_id' => $this->availabilityZone->datacentre_site_id,
                'region_id' => $this->availabilityZone->region_id
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

}

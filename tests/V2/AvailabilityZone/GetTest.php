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

    protected $availabilityZones;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();
        $this->availabilityZones = factory(AvailabilityZone::class, 2)->create([
            'region_id' => $region->getKey(),
            'name' => $this->faker->city(),
            'is_public' => false,
        ]);
    }


    public function testGetCollectionAsAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZones->first()->getKey(),
                'name' => $this->availabilityZones->first()->name,
            ])
            ->assertResponseStatus(200);

        $this->assertCount(2, $this->response->original);
    }

    public function testGetCollectionAsNonAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->availabilityZones->first()->getKey(),
                'name' => $this->availabilityZones->first()->name,
            ])
            ->assertResponseStatus(200);

        $this->assertCount(1, $this->response->original);
    }

    public function testGetPublicAvailabilityZoneAsAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $availabilityZone->getKey(),
                'name' => $availabilityZone->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPublicAvailabilityZoneAsNonAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $availabilityZone->getKey(),
                'name' => $availabilityZone->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = false;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $availabilityZone->getKey(),
                'name' => $availabilityZone->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsNonAdmin()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = false;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertResponseStatus(404);
    }

    public function testGetCollectionNonAdminPropertiesHidden()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $availabilityZone->id,
                'code' => $availabilityZone->code,
                'name' => $availabilityZone->name,
                'datacentre_site_id' => $availabilityZone->datacentre_site_id,
                'region_id' => $availabilityZone->region_id
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetailNonAdminPropertiesHidden()
    {
        $availabilityZone = $this->availabilityZones->first();
        $availabilityZone->is_public = true;
        $availabilityZone->save();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $availabilityZone->id,
                'code' => $availabilityZone->code,
                'name' => $availabilityZone->name,
                'datacentre_site_id' => $availabilityZone->datacentre_site_id,
                'region_id' => $availabilityZone->region_id
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

}

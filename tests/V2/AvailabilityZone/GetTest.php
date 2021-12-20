<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected AvailabilityZone $regionHiddenAz;

    public function setUp(): void
    {
        parent::setUp();
        // Hidden Region
        $hiddenRegion = factory(Region::class)->create([
            'id' => 'reg-hidden',
            'name' => 'Hidden Region',
            'is_public' => false,
        ]);

        // Availability Zone hidden by region
        $this->regionHiddenAz = factory(AvailabilityZone::class)->create([
            'id' => 'az-hidden',
            'name' => 'Region Hidden AZ',
            'region_id' => $hiddenRegion->id,
        ]);
    }

    public function testGetCollectionAsAdmin()
    {
        // Availability Zone only visible to admins
        factory(AvailabilityZone::class)->create([
            'is_public' => false,
        ]);

        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->seeJson([
            'id' => $this->regionHiddenAz->id,
            'name' => $this->regionHiddenAz->name,
        ])->assertResponseStatus(200);

        $this->assertCount(3, $this->response->original);
    }

    public function testGetCollectionAsNonAdmin()
    {
        // Availability Zone only visible to admins
        factory(AvailabilityZone::class)->create([
            'is_public' => false,
        ]);

        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);

        $this->assertCount(1, $this->response->original);
    }

    public function testGetPublicAvailabilityZoneAsAdmin()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);
    }

    public function testGetPublicAvailabilityZoneAsNonAdmin()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsAdmin()
    {
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsNonAdmin()
    {
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(404);
    }

    public function testGetCollectionNonAdminPropertiesHidden()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'code' => $this->availabilityZone()->code,
            'name' => $this->availabilityZone()->name,
            'datacentre_site_id' => $this->availabilityZone()->datacentre_site_id,
            'region_id' => $this->availabilityZone()->region_id
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->dontSeeJson([
            'is_public' => true
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetailNonAdminPropertiesHidden()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZone()->id,
            'code' => $this->availabilityZone()->code,
            'name' => $this->availabilityZone()->name,
            'datacentre_site_id' => $this->availabilityZone()->datacentre_site_id,
            'region_id' => $this->availabilityZone()->region_id
        ])->dontSeeJson([
            'is_public' => true
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);
    }

    public function testGetCapacities()
    {
        $availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id . '/capacities', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $availabilityZoneCapacity->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'type' => $availabilityZoneCapacity->type,
            'alert_warning' => $availabilityZoneCapacity->alert_warning,
            'alert_critical' => $availabilityZoneCapacity->alert_critical,
            'max' => $availabilityZoneCapacity->max
        ])->dontSeeJson([
            'id' => $this->regionHiddenAz->id,
        ])->assertResponseStatus(200);
    }
}

<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected AvailabilityZone $regionHiddenAz;

    public function setUp(): void
    {
        parent::setUp();
        // Hidden Region
        $hiddenRegion = Region::factory()->create([
            'id' => 'reg-hidden',
            'name' => 'Hidden Region',
            'is_public' => false,
        ]);

        // Availability Zone hidden by region
        $this->regionHiddenAz = AvailabilityZone::factory()->create([
            'id' => 'az-hidden',
            'name' => 'Region Hidden AZ',
            'region_id' => $hiddenRegion->id,
        ]);
    }

    public function testGetCollectionAsAdmin()
    {
        // Availability Zone only visible to admins
        AvailabilityZone::factory()->create([
            'is_public' => false,
        ]);

        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $get = $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonFragment([
            'id' => $this->regionHiddenAz->id,
            'name' => $this->regionHiddenAz->name,
        ])->assertStatus(200);

        $this->assertCount(3, $get->getOriginalContent());
    }

    public function testGetCollectionAsNonAdmin()
    {
        // Availability Zone only visible to admins
        AvailabilityZone::factory()->create([
            'is_public' => false,
        ]);

        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $get = $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);

        $this->assertCount(1, $get->getOriginalContent());
    }

    public function testGetCollectionAsInternalReseller()
    {
        // Availability Zone only visible to admins
        AvailabilityZone::factory()->create([
            'is_public' => false,
        ]);

        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $get = $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '7052-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonFragment([
            'id' => $this->regionHiddenAz->id,
            'name' => $this->regionHiddenAz->name,
        ])->assertStatus(200);

        $this->assertCount(3, $get->getOriginalContent());
    }

    public function testGetPublicAvailabilityZoneAsAdmin()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }

    public function testGetPublicAvailabilityZoneAsInternalReseller()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '7052-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }

    public function testGetPublicAvailabilityZoneAsNonAdmin()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsAdmin()
    {
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'name' => $this->availabilityZone()->name,
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }

    public function testGetPrivateAvailabilityZoneAsNonAdmin()
    {
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    public function testGetCollectionNonAdminPropertiesHidden()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'code' => $this->availabilityZone()->code,
            'name' => $this->availabilityZone()->name,
            'datacentre_site_id' => $this->availabilityZone()->datacentre_site_id,
            'region_id' => $this->availabilityZone()->region_id
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertJsonMissing([
            'is_public' => true
        ])->assertStatus(200);
    }

    public function testGetItemDetailNonAdminPropertiesHidden()
    {
        $this->availabilityZone()->is_public = true;
        $this->availabilityZone()->save();

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZone()->id,
            'code' => $this->availabilityZone()->code,
            'name' => $this->availabilityZone()->name,
            'datacentre_site_id' => $this->availabilityZone()->datacentre_site_id,
            'region_id' => $this->availabilityZone()->region_id
        ])->assertJsonMissing([
            'is_public' => true
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }

    public function testGetCapacities()
    {
        $availabilityZoneCapacity = AvailabilityZoneCapacity::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->get('/v2/availability-zones/' . $this->availabilityZone()->id . '/capacities', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $availabilityZoneCapacity->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'type' => $availabilityZoneCapacity->type,
            'alert_warning' => $availabilityZoneCapacity->alert_warning,
            'alert_critical' => $availabilityZoneCapacity->alert_critical,
            'max' => $availabilityZoneCapacity->max
        ])->assertJsonMissing([
            'id' => $this->regionHiddenAz->id,
        ])->assertStatus(200);
    }
}

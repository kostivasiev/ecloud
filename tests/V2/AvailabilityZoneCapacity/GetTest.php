<?php

namespace Tests\V2\AvailabilityZoneCapacity;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = Region::factory()->create();
        $this->vpc = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test',
                'region_id' => $this->region->id,
            ]);
        });
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->availabilityZoneCapacity = AvailabilityZoneCapacity::factory()->create([
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/availability-zone-capacities', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZoneCapacity->id,
            'availability_zone_id' => $this->availabilityZone->id,
            'type' => $this->availabilityZoneCapacity->type,
            'alert_warning' => $this->availabilityZoneCapacity->alert_warning,
            'alert_critical' => $this->availabilityZoneCapacity->alert_critical,
            'max' => $this->availabilityZoneCapacity->max
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->availabilityZoneCapacity->id,
            'availability_zone_id' => $this->availabilityZone->id,
            'type' => $this->availabilityZoneCapacity->type,
            'alert_warning' => $this->availabilityZoneCapacity->alert_warning,
            'alert_critical' => $this->availabilityZoneCapacity->alert_critical,
            'max' => $this->availabilityZoneCapacity->max
        ])->assertStatus(200);
    }
}

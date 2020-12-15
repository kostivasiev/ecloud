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
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/availability-zone-capacities', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZoneCapacity->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'type' => $this->availabilityZoneCapacity->type,
            'alert_warning' => $this->availabilityZoneCapacity->alert_warning,
            'alert_critical' => $this->availabilityZoneCapacity->alert_critical,
            'max' => $this->availabilityZoneCapacity->max
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->getKey(), [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->availabilityZoneCapacity->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'type' => $this->availabilityZoneCapacity->type,
            'alert_warning' => $this->availabilityZoneCapacity->alert_warning,
            'alert_critical' => $this->availabilityZoneCapacity->alert_critical,
            'max' => $this->availabilityZoneCapacity->max
        ])->assertResponseStatus(200);
    }
}

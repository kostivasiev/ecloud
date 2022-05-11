<?php

namespace Tests\V2\AvailabilityZoneCapacity;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Tests\TestCase;

class UpdateTest extends TestCase
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

    public function testValidDataIsSuccessful()
    {
        $availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);

        $data = [
            'availability_zone_id' => $availabilityZone->id,
            'type' => 'cpu',
            'alert_warning' => 40,
            'alert_critical' => 80,
            'max' => 90
        ];

        $this->patch('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->id, $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'availability_zone_capacities',
            $data,
            'ecloud'
        );
    }
}

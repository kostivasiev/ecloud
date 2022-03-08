<?php

namespace Tests\V2\AvailabilityZoneCapacity;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
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

    public function testValidDataSucceeds()
    {
        $data = [
            'availability_zone_id' => $this->availabilityZone->id,
            'type' => 'floating_ips',
            'alert_warning' => 60,
            'alert_critical' => 80,
            'max' => 95
        ];

        $this->post(
            '/v2/availability-zone-capacities',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas(
            'availability_zone_capacities',
            $data,
            'ecloud'
        );
    }
}

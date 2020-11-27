<?php

namespace Tests\V2\AvailabilityZoneCapacity;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
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

    public function testValidDataIsSuccessful()
    {
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $data = [
            'availability_zone_id' => $availabilityZone->getKey(),
            'type' => 'cpu',
            'alert_warning' => 40,
            'alert_critical' => 80,
            'max' => 90
        ];

        $this->patch('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->getKey(), $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'availability_zone_capacities',
            $data,
            'ecloud'
        )
            ->assertResponseStatus(200);
    }
}

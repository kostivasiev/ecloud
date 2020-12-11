<?php

namespace Tests\V2\AvailabilityZoneCapacity;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneCapacity;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
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

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->getKey(), [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(AvailabilityZoneCapacity::withTrashed()->findOrFail($this->availabilityZoneCapacity->getKey())->deleted_at);
    }
}

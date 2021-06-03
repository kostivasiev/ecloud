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
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $availabilityZoneCapacity;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = Vpc::withoutEvents(function () {
            return factory(Vpc::class)->create([
                'id' => 'vpc-test',
                'region_id' => $this->region->id
            ]);
        });
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->availabilityZoneCapacity = factory(AvailabilityZoneCapacity::class)->create([
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/availability-zone-capacities/' . $this->availabilityZoneCapacity->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(AvailabilityZoneCapacity::withTrashed()->findOrFail($this->availabilityZoneCapacity->id)->deleted_at);
    }
}

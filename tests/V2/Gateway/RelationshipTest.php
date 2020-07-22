<?php

namespace Tests\V2\Gateway;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class RelationshipTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->gateway = factory(Gateway::class, 1)->create([
            'availability_zone_id'       => $this->availabilityZone->getKey(),
        ])->first();
    }

    public function testAvailabilityZoneRelation()
    {
        $this->assertInstanceOf(AvailabilityZone::class, $this->gateway->availabilityZone);
        $this->assertEquals($this->availabilityZone->getKey(), $this->gateway->availabilityZone->getKey());
    }
}

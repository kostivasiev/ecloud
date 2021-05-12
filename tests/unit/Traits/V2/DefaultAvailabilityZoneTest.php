<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\AvailabilityZone;
use App\Traits\V2\DefaultAvailabilityZone;
use Tests\TestCase;

class DefaultAvailabilityZoneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSetsAvailabilityZoneWithNoneSet()
    {
        $mock = $this->getMockForTrait(DefaultAvailabilityZone::class);
        $mock->setDefaultAvailabilityZone($this->instance());

        $this->assertEquals($this->availabilityZone()->id, $this->instance()->availabilityZone->id);
    }

    public function testAvailabilityZoneNotOverridden()
    {
        $this->instance()->availabilityZone = factory(AvailabilityZone::class)->create([
            'id' => 'az-old'
        ]);

        $mock = $this->getMockForTrait(DefaultAvailabilityZone::class);
        $mock->setDefaultAvailabilityZone($this->instance());

        $this->assertEquals('az-old', $this->instance()->availabilityZone->id);
    }
}

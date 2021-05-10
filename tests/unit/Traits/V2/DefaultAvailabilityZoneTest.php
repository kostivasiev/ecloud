<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Nic;
use App\Rules\V2\IpAvailable;
use App\Traits\V2\DefaultAvailabilityZone;
use Faker\Factory as Faker;
use Illuminate\Database\QueryException;
use Laravel\Lumen\Testing\DatabaseMigrations;
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

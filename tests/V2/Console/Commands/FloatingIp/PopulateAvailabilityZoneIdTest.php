<?php
namespace Tests\V2\Console\Commands\FloatingIp;

use Illuminate\Console\Command;
use Tests\TestCase;

class PopulateAvailabilityZoneIdTest extends TestCase
{
    public function testSuccess()
    {
        $this->floatingIp()->availability_zone_id = null;
        $this->floatingIp()->save();

        $this->assertNull($this->floatingIp()->availability_zone_id);

        $exitCode = $this->artisan('floating-ip:populate-availability-zone-id');

        $this->assertEquals(Command::SUCCESS ,$exitCode);

        $this->floatingIp()->refresh();
        $this->assertEquals($this->availabilityZone()->id, $this->floatingIp()->availability_zone_id);
    }
}
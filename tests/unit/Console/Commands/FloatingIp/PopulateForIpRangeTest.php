<?php

namespace Tests\unit\Console\Commands\FloatingIp;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PopulateForIpRangeTest extends TestCase
{
    public function testSuccess()
    {
        Event::fake([Created::class]);

        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30 --force');
        $this->assertEquals(0, $exitCode);
    }

    public function testVpcNotExistReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range vpc-invalid ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30 --force');
        $this->assertEquals(1, $exitCode);
    }

    public function testAzNotExistReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id . ' az-invalid --ip-range 1.2.3.4/30 --force');
        $this->assertEquals(1, $exitCode);
    }

    public function testInvalidIpRangeReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/40 --force');
        $this->assertEquals(1, $exitCode);
    }
}

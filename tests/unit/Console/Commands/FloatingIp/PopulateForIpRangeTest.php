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

        $pendingCommand = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30 --force');
        $pendingCommand->assertSuccessful();
    }

    public function testVpcNotExistReturnsError()
    {
        $pendingCommand = $this->artisan('floating-ip:populate-for-ip-range vpc-invalid ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30 --force');
        $pendingCommand->assertFailed();
    }

    public function testAzNotExistReturnsError()
    {
        $pendingCommand = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id . ' az-invalid --ip-range 1.2.3.4/30 --force');
        $pendingCommand->assertFailed();
    }

    public function testInvalidIpRangeReturnsError()
    {
        $pendingCommand = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/40 --force');
        $pendingCommand->assertFailed();
    }
}

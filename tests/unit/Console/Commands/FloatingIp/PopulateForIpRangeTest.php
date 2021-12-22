<?php

namespace Tests\unit\Console\Commands\FloatingIp;

use App\Console\Commands\Billing\SetFriendlyNames;
use App\Models\V2\BillingMetric;
use Database\Seeders\BillingMetricSeeder;
use Tests\TestCase;

class PopulateForIpRangeTest extends TestCase
{
    public function testSuccess()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30');
        $this->assertEquals(0, $exitCode);
    }

    public function testVpcNotExistReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range vpc-invalid ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/30');
        $this->assertEquals(1, $exitCode);
    }

    public function testAzNotExistReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id . ' az-invalid --ip-range 1.2.3.4/30');
        $this->assertEquals(1, $exitCode);
    }

    public function testInvalidIpRangeReturnsError()
    {
        $exitCode = $this->artisan('floating-ip:populate-for-ip-range ' . $this->vpc()->id .  ' ' . $this->availabilityZone()->id . ' --ip-range 1.2.3.4/40');
        $this->assertEquals(1, $exitCode);
    }
}

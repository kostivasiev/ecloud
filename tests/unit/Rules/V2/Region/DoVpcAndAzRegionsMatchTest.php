<?php

namespace Tests\unit\Rules\V2\Region;

use App\Rules\V2\Region\DoVpcAndAzRegionsMatch;
use Illuminate\Support\Facades\Request;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DoVpcAndAzRegionsMatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Request::shouldReceive('input')->with('vpc_id')->andReturn($this->vpc()->id);
        Request::shouldReceive('input')->with('availability_zone_id')->andReturn($this->availabilityZone()->id);
    }

    /** @test */
    public function isSameRegionPassesVpc()
    {
        $rule = new DoVpcAndAzRegionsMatch('vpc_id', $this->vpc()->id);
        $this->assertTrue($rule->passes('availability_zone_id', $this->availabilityZone()->id));
    }

    /** @test */
    public function isDifferentRegionFailsVpc()
    {
        $this->vpc()->setAttribute('region_id', 'reg-fail')->saveQuietly();
        $this->vpc()->refresh();
        $rule = new DoVpcAndAzRegionsMatch('vpc_id', $this->vpc()->id);
        $this->assertFalse($rule->passes('availability_zone_id', $this->availabilityZone()->id));
    }

    /** @test */
    public function isSameRegionPassesAz()
    {
        $rule = new DoVpcAndAzRegionsMatch('availability_zone_id', $this->availabilityZone()->id);
        $this->assertTrue($rule->passes('vpc_id', $this->vpc()->id));
    }

    /** @test */
    public function isDifferentRegionFailsAz()
    {
        $this->vpc()->setAttribute('region_id', 'reg-fail')->saveQuietly();
        $this->vpc()->refresh();
        $rule = new DoVpcAndAzRegionsMatch('availability_zone_id', $this->availabilityZone()->id);
        $this->assertFalse($rule->passes('vpc_id', $this->vpc()->id));
    }
}

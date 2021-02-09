<?php

namespace Tests\unit\Volume;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Rules\V2\IsMaxVolumeLimitReached;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IsMaxVolumeLimitReachedTest extends TestCase
{
    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected IsMaxVolumeLimitReached $rule;
    protected Instance $instance;
    protected $volume;
    protected int $volumeCount;

    protected function setUp(): void
    {
        parent::setUp();
        // Set volume limit to 1
        Config::set('volume.instance.limit', 1);
        $this->rule = new IsMaxVolumeLimitReached();
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc()->getKey(),
            'name' => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'availability_zone_id' => $this->availabilityZone()->getKey(),
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
        ]);
        $this->volumeCount = 1;
        $this->volume = factory(Volume::class, 2)->create([
            'name' => 'Volume 1',
            'vpc_id' => $this->vpc()->getKey(),
            'availability_zone_id' => $this->availabilityZone()->getKey()
        ])->each(function ($volume) {
            $volume->name = 'Volume ' . $this->volumeCount;
            $volume->save();
            $this->volumeCount++;
        });
    }

    public function testLimits()
    {
        // Test with one volume limit, and then attach that volume
        $this->assertTrue($this->rule->passes('', $this->instance->getKey()));
        $this->instance->volumes()->attach($this->volume[0]);
        $this->instance->save();

        // Now assert that we're at the limit
        $this->assertFalse($this->rule->passes('', $this->instance->getKey()));
    }
}
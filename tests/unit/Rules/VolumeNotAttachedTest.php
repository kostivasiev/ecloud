<?php
namespace Tests\unit\Rules;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Rules\V2\VolumeNotAttached;
use Illuminate\Support\Str;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeNotAttachedTest extends TestCase
{
    use DatabaseMigrations;
    
    protected Instance $instance;
    protected Volume $volume;
    protected Vpc $vpc;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->instance = factory(Instance::class)->create([
            'id' => 'i-abc123xyz',
            'name' => 'i-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'appliance_version_id' => Str::uuid(),
            'vcpu_cores' => 1,
            'ram_capacity' => 8,
            'availability_zone_id' => $this->availabilityZone()->id,
            'locked' => false,
            'platform' => 'Linux',
            'backup_enabled' => false,
        ]);
        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'name' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 30,
            'vmware_uuid' => Str::uuid(),
        ]);
    }

    public function testVolumeAlreadyAttached()
    {
        $this->instance->volumes()->attach($this->volume);
        $rule = new VolumeNotAttached($this->volume);
        $this->assertFalse($rule->passes('', $this->instance->id));
    }

    public function testVolumeNotAttached()
    {
        $rule = new VolumeNotAttached($this->volume);
        $this->assertTrue($rule->passes('', $this->instance->id));
    }

    public function testVolumeIsntTheOneAttached()
    {
        // create a different volume and attach it
        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'name' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 30,
            'vmware_uuid' => Str::uuid(),
        ]);
        $this->instance->volumes()->attach($volume);
        $rule = new VolumeNotAttached($this->volume);
        $this->assertTrue($rule->passes('', $this->instance->id));
    }
}
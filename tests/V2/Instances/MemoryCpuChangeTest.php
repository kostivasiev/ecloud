<?php
namespace Tests\V2\Instances;

use App\Events\V2\ComputeChanged;
use App\Listeners\V2\ComputeChange;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Services\NsxService;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class MemoryCpuChangeTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected AvailabilityZone $availability_zone;
    protected $event;
    protected $instance;
    protected $listener;
    protected Region $region;
    protected ?string $capturedUri = null;
    protected ?array $capturedParams = null;


    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->instance = \Mockery::mock(Instance::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->instance->shouldReceive('getOnlineAttribute')
            ->andReturnTrue();
        $this->instance->shouldReceive('getVolumeCapacityAttribute')
            ->andReturn(10);
        $this->instance->availabilityZone = $this->availability_zone;
        $this->instance->id = 'i-abc123xyz';
        $this->instance->vpc_id = 'vpc-abc123xyz';

        $this->event = new ComputeChanged($this->instance);

        $this->listener = \Mockery::mock(ComputeChange::class)
            ->makePartial();
        $this->listener->shouldReceive('put')
            ->with(
                \Mockery::capture($this->capturedUri),
                \Mockery::capture($this->capturedParams)
            )
            ->andReturnTrue();
    }

    public function testMemoryChangeRamCapacity()
    {
        // First check Linux 2Gb change (no reboot)
        $this->instance->platform = 'Linux';
        $this->instance->ram_capacity = 2048;
        $this->instance->vcpu_cores = 1;

        $this->listener->handle($this->event);
        $this->assertEquals($this->instance->ram_capacity, $this->capturedParams['ramGB']);
        $this->assertEquals($this->instance->vcpu_cores, $this->capturedParams['numCpu']);
        $this->assertFalse($this->capturedParams['guestShutdown']);

        // Now check Linux 4Gb change (reboot required)
        $this->instance->ram_capacity = 4096;
        $this->instance->vcpu_cores = 2;
        $this->listener->handle($this->event);
        $this->assertEquals($this->instance->ram_capacity, $this->capturedParams['ramGB']);
        $this->assertEquals($this->instance->vcpu_cores, $this->capturedParams['numCpu']);
        $this->assertTrue($this->capturedParams['guestShutdown']);

        // Now check Windows 2Gb change
        $this->instance->platform = 'Windows';
        $this->instance->ram_capacity = 2048;
        $this->instance->vcpu_cores = 1;
        $this->listener->handle($this->event);
        $this->assertEquals($this->instance->ram_capacity, $this->capturedParams['ramGB']);
        $this->assertEquals($this->instance->vcpu_cores, $this->capturedParams['numCpu']);
        $this->assertFalse($this->capturedParams['guestShutdown']);

        // Now check Windows 4Gb change (no reboot)
        $this->instance->ram_capacity = 4096;
        $this->instance->vcpu_cores = 2;
        $this->listener->handle($this->event);
        $this->assertEquals($this->instance->ram_capacity, $this->capturedParams['ramGB']);
        $this->assertEquals($this->instance->vcpu_cores, $this->capturedParams['numCpu']);
        $this->assertFalse($this->capturedParams['guestShutdown']);

        // Now check Windows 17Gb change (reboot required)
        $this->instance->ram_capacity = 17408;
        $this->instance->vcpu_cores = 4;
        $this->listener->handle($this->event);
        $this->assertEquals($this->instance->ram_capacity, $this->capturedParams['ramGB']);
        $this->assertEquals($this->instance->vcpu_cores, $this->capturedParams['numCpu']);
        $this->assertTrue($this->capturedParams['guestShutdown']);
    }
}

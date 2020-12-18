<?php
namespace Tests\V2\Instances;

use App\Listeners\V2\Instance\ComputeChange;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class MemoryCpuChangeTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected AvailabilityZone $availability_zone;
    protected $instance;
    protected Region $region;
    protected $appliance;
    protected $appliance_version;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('put')->andReturn(
            new Response(200)
        );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testMemoryChangeRamCapacity()
    {
        Event::fake();

        $instance = factory(Instance::class)->create([
            'id' => 'i-abc123',
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'UpdateTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'backup_enabled' => false,
        ]);

        $instance->vcpu_cores = 2;
        $instance->ram_capacity = 2048;
        $instance->save();

        Event::assertDispatched(\App\Events\V2\Instance\Updated::class, function ($event) use ($instance) {
            return $event->model->id === $instance->id;
        });

        $listener = \Mockery::mock(ComputeChange::class)->makePartial();

        $listener->handle(new \App\Events\V2\Instance\Updated($instance));
    }
}

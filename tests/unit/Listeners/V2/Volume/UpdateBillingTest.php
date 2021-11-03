<?php
namespace Tests\unit\Listeners\V2\Volume;

use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    use LoadBalancerMock;

    protected Volume $volume;
    protected Task $task;
    protected Instance $instance;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'capacity' => 20,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->volume->instances()->attach($this->instance());
        });
    }

    public function testResizingVolumeAddsBillingMetric()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->volume);
        });

        // Check that the volume billing metric is added
        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('disk.capacity', $metric->key);
    }

    public function testDefaultIopsBilling()
    {
        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertEquals(300, $this->volume->iops);
        $this->assertEquals('disk.capacity.300', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testUnattachedVolumeWithNonDefaultIops()
    {
        Volume::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'iops' => 600,
            ]);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertEquals(600, $this->volume->iops);
        $this->assertEquals('disk.capacity.600', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testAttachedVolumeWithDefaultIops()
    {
        Model::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);

            $this->instance = factory(Instance::class)->create([
                'id' => 'i-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'image_id' => $this->image()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
            $this->instance->volumes()->attach($this->volume);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertEquals(300, $this->volume->iops);
        $this->assertEquals('disk.capacity.300', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testAttachedVolumeWithNonDefaultIops()
    {
        Model::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'iops' => 600
            ]);

            $this->instance = factory(Instance::class)->create([
                'id' => 'i-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'image_id' => $this->image()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->instance->volumes()->attach($this->volume->id);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertEquals(600, $this->volume->iops); // wrong - should be 600
        $this->assertEquals('disk.capacity.600', $metric->key);
        $this->assertNull($metric->end);
    }

    public function testAttachedVolumeNewIopsExistingMetric()
    {
        $originalBilling = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => 'vol-abc123xyz',
            'vpc_id' => 'vpc-test',
            'reseller_id' => '1',
            'key' => 'disk.capacity.300',
            'value' => '100',
            'start' => (string) Carbon::now(),
            'end' => null,
            'category' => 'Storage',
            'price' => null,
        ]);


        Model::withoutEvents(function() {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'iops' => 600
            ]);

            $this->instance = factory(Instance::class)->create([
                'id' => 'i-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'image_id' => $this->image()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->instance->volumes()->attach($this->volume);
        });

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        // Update the origin billingMetric now it's been ended
        $originalBilling->refresh();

        $this->assertEquals('disk.capacity.300', $originalBilling->key);
        $this->assertNotNull($originalBilling->end);

        $metric = BillingMetric::getActiveByKey($this->volume, 'disk.capacity.600');

        $this->assertEquals($originalBilling->resource_id, $metric->resource_id);
        $this->assertNull($metric->end);
    }

    public function testLoadBalancerInstanceVolumesIgnored()
    {
        $this->instance()->loadBalancer()->associate($this->loadBalancer())->save();

        $dispatchResourceSyncedEventListener = new \App\Listeners\V2\Volume\UpdateBilling();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Task\Updated($this->volume));

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertNull($metric);
    }
}

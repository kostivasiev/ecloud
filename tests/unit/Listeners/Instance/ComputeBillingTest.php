<?php
namespace Tests\unit\Listeners\Instance;

use App\Listeners\V2\Instance\ComputeChange;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ComputeBillingTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('put')->andReturn(
            new Response(200)
        );
    }

    public function testComputeChangeBilling()
    {
        // compute metrics created on deploy
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->getKey(),
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $originalRamMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->getKey(),
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        // Update the instance compute values
        $this->instance()->vcpu_cores = 2;
        $this->instance()->ram_capacity = 2048;
        $this->instance()->save();

        Event::assertDispatched(\App\Events\V2\Instance\Updated::class, function ($event)  {
            return $event->model->id === $this->instance()->id;
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Instance\Saving($this->instance()));

        $sync = Sync::where('resource_id', $this->instance()->id)->first();

        $computeChangeListener = \Mockery::mock(ComputeChange::class)->makePartial();
        $computeChangeListener->handle(new \App\Events\V2\Instance\Updated($this->instance()));

        // sync set to complete by the ComputeChange listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the vcpu billing metric is added
        $updateVcpuBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateVcpuBilling::class)->makePartial();
        $updateVcpuBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $vcpuMetric = BillingMetric::getActiveByKey($this->instance(), 'vcpu.count');
        $this->assertNotNull($vcpuMetric);
        $this->assertEquals(2, $vcpuMetric->value);

        // Check that the ram billing metric is added
        $updateRamBillingListener = \Mockery::mock(\App\Listeners\V2\Instance\UpdateRamBilling::class)->makePartial();
        $updateRamBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $ramMetric = BillingMetric::getActiveByKey($this->instance(), 'ram.capacity');
        $this->assertNotNull($ramMetric);
        $this->assertEquals(2048, $ramMetric->value);

        // Check existing metrics were ended
        $originalVcpuMetric->refresh();
        $originalRamMetric->refresh();

        $this->assertNotNull($originalVcpuMetric->end);
        $this->assertNotNull($originalRamMetric->end);
    }
}

<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\EndComputeBilling;
use App\Models\V2\BillingMetric;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EndComputeBillingTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEndComputeBillingJob()
    {
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
        Event::fake([JobFailed::class]);
        dispatch(new EndComputeBilling($this->instance()));
        Event::assertNotDispatched(JobFailed::class);

        $originalVcpuMetric->refresh();
        $originalRamMetric->refresh();
        $this->assertNotNull($originalVcpuMetric->end);
        $this->assertNotNull($originalRamMetric->end);
    }

    public function testEndComputeOnlyUpdatesActiveResources()
    {
        $endDate = '2020-12-31T22:22:22+0:00';
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $endedVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test-end1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
            'end' => $endDate,
        ]);
        $endedRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test-end2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'end' => $endDate,
        ]);

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
        Event::fake([JobFailed::class]);
        dispatch(new EndComputeBilling($this->instance()));
        Event::assertNotDispatched(JobFailed::class);

        $originalVcpuMetric->refresh();
        $originalRamMetric->refresh();
        $endedVcpuMetric->refresh();
        $endedRamMetric->refresh();
        $this->assertNotNull($originalVcpuMetric->end);
        $this->assertNotNull($originalRamMetric->end);
        $this->assertEquals($endDate, $endedVcpuMetric->end);
        $this->assertEquals($endDate, $endedRamMetric->end);
    }
}

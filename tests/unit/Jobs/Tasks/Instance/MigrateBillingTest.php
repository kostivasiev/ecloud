<?php
namespace Tests\unit\Jobs\Tasks\Instance;

use App\Jobs\Instance\EndPublicBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class MigrateBillingTest extends TestCase
{
    private Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDON,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
    }

    public function testBillingEndsOnPublicInstance()
    {
        // compute metrics created on deploy
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'category' => 'Compute',
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => Carbon::now(),
        ]);

        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'category' => 'Compute',
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => Carbon::now(),
        ]);

        $originalLicenseMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test3',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'license.windows',
            'value' => 1,
            'start' => Carbon::now(),
        ]);

        $this->instance()->host_group_id = 'hg-aaabbbccc';
        $this->instance()->image->setAttribute('platform', 'Windows')->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instance());
        });

        // Check that the ram billing metric is added
        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $updateVcpuBillingListener = new \App\Listeners\V2\Instance\UpdateVcpuBilling();
        $updateVcpuBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateWindowsLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $this->instance()->refresh();

        $originalVcpuMetric->refresh();
        $originalRamMetric->refresh();
        $originalLicenseMetric->refresh();

        $this->assertNotNull($originalVcpuMetric->end);
        $this->assertNotNull($originalRamMetric->end);
        $this->assertNotNull($originalLicenseMetric->end);
    }

    public function testBillingStartsOnAPublicInstance()
    {
        $this->instance()->host_group_id = '';
        $this->instance()->image->setAttribute('platform', 'Windows')->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->instance());
        });

        // Check that the ram billing metric is added
        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateRamBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $updateVcpuBillingListener = new \App\Listeners\V2\Instance\UpdateVcpuBilling();
        $updateVcpuBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateWindowsLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));
        $this->instance()->refresh();

        $metrics = $this->instance()->billingMetrics()->get()->toArray();
        $this->assertEquals('ram.capacity', $metrics[0]['key']);
        $this->assertNull($metrics[0]['end']);
        $this->assertEquals('vcpu.count', $metrics[1]['key']);
        $this->assertNull($metrics[1]['end']);
        $this->assertEquals('license.windows', $metrics[2]['key']);
        $this->assertNull($metrics[2]['end']);
    }
}
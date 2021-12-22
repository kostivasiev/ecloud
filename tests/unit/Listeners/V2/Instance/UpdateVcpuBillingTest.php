<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateVcpuBillingTest extends TestCase
{
    use LoadBalancerMock;

    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testVcpuChangeBilling()
    {
        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDON,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
        // compute metrics created on deploy
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        // Update the instance compute values
        $this->instance()->vcpu_cores = 2;

        Model::withoutEvents(function () {
            $this->sync = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->sync->resource()->associate($this->instance());
        });

        // Check that the vcpu billing metric is added
        $updateVcpuBillingListener = new \App\Listeners\V2\Instance\UpdateVcpuBilling();
        $updateVcpuBillingListener->handle(new \App\Events\V2\Task\Updated($this->sync));

        $vcpuMetric = BillingMetric::getActiveByKey($this->instance(), 'vcpu.count');
        $this->assertNotNull($vcpuMetric);
        $this->assertEquals(2, $vcpuMetric->value);

        // Check existing metric was ended
        $originalVcpuMetric->refresh();

        $this->assertNotNull($originalVcpuMetric->end);
    }

    public function testLoadBalancerInstancesIgnored()
    {
        $this->instance();

        $this->instance()->loadBalancer()->associate($this->loadBalancer())->save();

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        $updateRamBillingListener = new \App\Listeners\V2\Instance\UpdateVcpuBilling();
        $updateRamBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $billingMetric = BillingMetric::getActiveByKey($this->instance(), 'vcpu.count');

        $this->assertNull($billingMetric);
    }
}

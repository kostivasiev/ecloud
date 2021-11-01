<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateLicenseBillingTest extends TestCase
{
    use LoadBalancerMock;

    private Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instance());
        });
    }

    public function testInstertLicenseBilling()
    {
        $this->instance()->vcpu_cores = 1;
        $this->instance()->platform = 'Windows';

        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $vcpuMetric = BillingMetric::getActiveByKey($this->instance(), 'license.windows');
        $this->assertNotNull($vcpuMetric);
        $this->assertEquals(1, $vcpuMetric->value);
    }

    public function testUpdateLicenseChangeBilling()
    {
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'license.windows',
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instance()->vcpu_cores = 5;
        $this->instance()->platform = 'Windows';

        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $vcpuMetric = BillingMetric::getActiveByKey($this->instance(), 'license.windows');
        $this->assertNotNull($vcpuMetric);
        $this->assertEquals(5, $vcpuMetric->value);

        // Check existing metric was ended
        $originalVcpuMetric->refresh();
        $this->assertNotNull($originalVcpuMetric->end);
    }

    public function testLoadBalancerInstancesIgnored()
    {
        $this->instance()->vcpu_cores = 1;
        $this->instance()->platform = 'Windows'; // LB nodes are not Windows, but just for testing...

        $this->instance()->loadBalancer()->associate($this->loadBalancer())->save();

        $updateLicenseBillingListener = new \App\Listeners\V2\Instance\UpdateLicenseBilling();
        $updateLicenseBillingListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $vcpuMetric = BillingMetric::getActiveByKey($this->instance(), 'license.windows');
        $this->assertNull($vcpuMetric);
    }
}

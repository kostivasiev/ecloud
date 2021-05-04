<?php
namespace Tests\unit\Listeners\V2\Instance;

use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateVcpuBillingTest extends TestCase
{
    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testVcpuChangeBilling()
    {
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

        Model::withoutEvents(function() {
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
}

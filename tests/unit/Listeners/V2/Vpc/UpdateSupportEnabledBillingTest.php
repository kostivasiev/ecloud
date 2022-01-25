<?php
namespace Tests\unit\Listeners\V2\Vpc;

use App\Listeners\V2\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateSupportEnabledBillingTest extends TestCase
{
    public function testStartsBillingMetricForSupportEnabled()
    {
        $this->vpc()->setAttribute('support_enabled', true)->saveQuietly();
        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName()));

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateSupportEnabledBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName());
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->value);
    }

    public function testEndsBillingMetricForSupportEnabled()
    {
        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => UpdateSupportEnabledBilling::getKeyName(),
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_DELETE
            ]);
            $task->resource()->associate($this->vpc());
            return $task;
        });


        $listener = new \App\Listeners\V2\Vpc\UpdateSupportEnabledBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName()));
    }
}

<?php

namespace Tests\Unit\Listeners\V2\Router;

use App\Listeners\V2\Router\UpdateBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    public function setUp(): void
    {
         parent::setUp();
    }

    public function testRouterBillingMetricCreated()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });

        // check the billing metric is added
        $eventListener = new UpdateBilling();
        $eventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::where('resource_id', $this->router()->id)->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('throughput.10Gb', $metric->key);
    }

    public function testRouterBillingMetricThroughputUpdated()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });

        // check the billing metric is added
        $eventListener = new UpdateBilling();
        $eventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric = BillingMetric::where('resource_id', $this->router()->id)->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('throughput.10Gb', $metric->key);

        $newThroughput = RouterThroughput::withoutEvents(function () {
            return RouterThroughput::factory()->create([
                'id' => 'rt-test2',
                'name' => '100Gb',
            ]);
        });
        $this->router()->setAttribute('router_throughput_id', $newThroughput->getKey())->saveQuietly();
        $this->router()->refresh();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'task-2',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });
        $eventListener = new UpdateBilling();
        $eventListener->handle(new \App\Events\V2\Task\Updated($this->task));

        $metric->refresh();
        $newMetric = BillingMetric::where('resource_id', $this->router()->id)
            ->whereNull('end')
            ->first();

        $this->assertNotNull($metric->end);
        $this->assertNull($newMetric->end);
        $this->assertNotEquals($metric->id, $newMetric->id);
    }
}

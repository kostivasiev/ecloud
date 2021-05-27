<?php
namespace Tests\unit\Listeners\V2\Vpc;

use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': advanced networking',
        ])->each(function ($product) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.001388889,
            ]);
        });
    }

    public function testCreateVpcAdvancedNetworkingBillingMetric()
    {
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpc());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals('networking.advanced', $metric->key);
        $this->assertEquals(0, $metric->value);
    }

    public function testCreateInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 0,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(1024, $metric->value);
    }

    public function testResizeInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instance()->ram_capacity = 2048;
        $this->instance()->saveQuietly();

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instance());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(2048, $metric->value);
    }

    public function testDeleteInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $instance = Instance::withoutEvents(function() {
            factory(Instance::class)->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'ram_capacity' => 1024,
            ]);
            return factory(Instance::class)->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'ram_capacity' => 1024,
            ]);
        });

        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 2048,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $task = Model::withoutEvents(function() use ($instance) {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_DELETE
            ]);
            $task->resource()->associate($instance);
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(1024, $metric->value);
    }
}

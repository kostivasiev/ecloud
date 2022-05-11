<?php

namespace Tests\Unit\Listeners\V2\Vpc;

use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateAdvancedNetworkingBillingTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': advanced networking',
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => 0.001388889,
            ]);
        });

        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();
        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => ''
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });
    }

    public function testCreateInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced'));

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instanceModel());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(1024, $metric->value);
    }

    public function testResizeInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $originalMetric = BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->instanceModel()->ram_capacity = 2048;
        $this->instanceModel()->saveQuietly();

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instanceModel());
            return $task;
        });

        $listener = new \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(2048, $metric->value);
    }

    public function testDeleteInstanceUpdatesAdvancedNetworkingBillingMetric()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $instance = Instance::withoutEvents(function () {
            Instance::factory()->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ram_capacity' => 1024,
            ]);
            return Instance::factory()->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ram_capacity' => 1024,
            ]);
        });

        $originalMetric = BillingMetric::factory()->create([
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

        $listener = new \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $metric = BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced');
        $this->assertNotNull($metric);
        $this->assertEquals(1024, $metric->value);
    }


    public function testDeleteAllInstancesEndsBillingMetric()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $instance = Instance::withoutEvents(function () {
            return Instance::factory()->create([
                'id' => 'i-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'ram_capacity' => 1024,
            ]);
        });

        $originalMetric = BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 1024,
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

        $listener = new \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced'));
    }

    public function testManagedInstancesAreIgnored()
    {
        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced'));

        $this->assertEquals(0, $this->vpc()->instances()->sum('ram_capacity'));

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->instanceModel());
            return $task;
        });

        $this->instanceModel()->loadBalancer()->associate($this->loadBalancer())->save();

        $listener = new \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling();
        $listener->handle(new \App\Events\V2\Task\Updated($task));

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), 'networking.advanced'));
    }
}

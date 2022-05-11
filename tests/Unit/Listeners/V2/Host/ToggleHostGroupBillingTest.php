<?php

namespace Tests\Unit\Listeners\V2\Host;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use App\Models\V2\Host;

class ToggleHostGroupBillingTest extends TestCase
{
    protected Task $task;

    protected Product $product;

    public function setUp(): void
    {
        parent::setUp();

        // Setup HostGroup product
        $this->product = Product::factory()->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': hostgroup',
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        ProductPrice::factory()->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.0000115314,
        ]);

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

    public function testCreatingHostEndsHostGroupBilling()
    {
        $this->hostGroup();

        $hostGroupBillingMetric = BillingMetric::factory()->create([
            'resource_id' => $this->hostGroup()->id,
            'key' => 'hostgroup',
            'value' => 1,
        ]);

        $this->assertNull($hostGroupBillingMetric->end);

        $this->host();

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->host());
            return $task;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $this->assertNotNull($hostGroupBillingMetric->refresh()->end);
    }

    public function testDeletingHostEmptyHostGroupStartsBilling()
    {
        $this->hostGroup();
        $this->host();

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'resource_id' => $this->host()->id,
                'completed' => true,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->host());
            return $task;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNotNull($metric);
        $this->assertEquals(0.0000115314, $metric->price);
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }

    public function testDeletingHostNotEmptyHostGroupDoesNotStartBilling()
    {
        $this->hostGroup();
        $this->host();

        // Create a 2nd host
        $newHost = Model::withoutEvents(function() {
            return Host::factory()->create([
                'id' => 'h-test-2',
                'name' => 'h-test-2',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);

        $task = Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'resource_id' => $this->host()->id,
                'completed' => true,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->host());
            return $task;
        });

        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        // Check that no host group billing was added
        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);
    }
}

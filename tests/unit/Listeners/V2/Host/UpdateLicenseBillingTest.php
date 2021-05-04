<?php

namespace Tests\unit\Listeners\V2\Host;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateLicenseBillingTest extends TestCase
{
    protected Task $task;

    protected Product $product;

    public function setUp(): void
    {
        parent::setUp();

        // Setup Host product
        $this->product = factory(Product::class)->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': host windows-os-license',
            'product_category' => 'eCloud',
            'product_subcategory' => 'License',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.0164384,
        ]);
    }

    public function testCreatingHostInWindowsEnabledHostGroupAddsBilling()
    {
        $host = Model::withoutEvents(function() {
            return factory(\App\Models\V2\Host::class)->create([
                'id' => 'h-test-2',
                'name' => 'h-test-2',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->hostSpec()->cpu_sockets = 2;
        $this->hostSpec()->cpu_cores = 6;

        $task = Model::withoutEvents(function() use ($host) {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($host);
            return $task;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\UpdateLicenseBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($host, 'host.license.windows');
        $this->assertNotNull($metric);
        $this->assertEquals(0.0164384, $metric->price);
        $this->assertEquals(8, $metric->value); // because billing product is per 2 core pack
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }

    public function testCreatingHostInWindowsEnabledHostGroupAddsBillingMinCores()
    {
        $host = Model::withoutEvents(function() {
            return factory(\App\Models\V2\Host::class)->create([
                'id' => 'h-test-2',
                'name' => 'h-test-2',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->hostSpec()->cpu_sockets = 2;
        $this->hostSpec()->cpu_cores = 6;

        $task = Model::withoutEvents(function() use ($host) {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($host);
            return $task;
        });

        // Billing is for at least config('host.billing.windows.min_cores') cores / 2
        config()->set('host.billing.windows.min_cores', 20);

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\UpdateLicenseBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($host, 'host.license.windows');
        $this->assertNotNull($metric);
        $this->assertEquals(0.0164384, $metric->price);
        $this->assertEquals(10, $metric->value);
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }

    public function testCreatingHostInWindowsNotEnabledHostGroupDoesNotAddBilling()
    {
        $hostGroup = Model::withoutEvents(function() {
            return factory(HostGroup::class)->create([
                'id' => 'hg-test-2',
                'name' => 'hg-test-2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => false,
            ]);
        });

        $host = Model::withoutEvents(function() use ($hostGroup) {
            return factory(\App\Models\V2\Host::class)->create([
                'id' => 'h-test-2',
                'name' => 'h-test-2',
                'host_group_id' => $hostGroup->id,
            ]);
        });

        $task = Model::withoutEvents(function() use ($host) {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($host);
            $task->save();

            return $task;
        });

        // Check that no the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\UpdateLicenseBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($task));

        $metric = BillingMetric::getActiveByKey($host, 'host.license.windows');
        $this->assertNull($metric);
    }
}

<?php

namespace Tests\unit\Listeners\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected Task $task;
    protected HostGroup $hostGroup;

    protected Product $product;
    protected ProductPrice $productPrice;

    public function setUp(): void
    {
        parent::setUp();

        // Setup HostGroup product
        $this->product = factory(Product::class)->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': hostgroup',
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        $this->productPrice = factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.0000115314,
        ]);
    }

    public function testCreatingHostGroupAddsBillingMetric()
    {
        $this->hostGroup();

        Task::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->hostGroup());
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\HostGroup\UpdateBilling;
        $UpdateBillingListener->handle(new \App\Events\V2\Sync\Updated($this->task));

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNotNull($metric);
        $this->assertEquals(0.0000115314, $metric->price);
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }
}

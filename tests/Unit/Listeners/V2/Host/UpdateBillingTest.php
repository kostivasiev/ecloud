<?php

namespace Tests\Unit\Listeners\V2\Host;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class UpdateBillingTest extends TestCase
{
    protected Sync $sync;

    protected Product $product;

    public function setUp(): void
    {
        parent::setUp();

        // Setup Host product
        $this->product = Product::factory()->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': hs-test',
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        ProductPrice::factory()->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.00694444,
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

    public function testCreatingHostAddsBillingMetric()
    {
        $this->host();
        //$sync = $this->host()->syncs()->latest()->first();
        // Even though $this->>host() will mock the sync and we can use above, use this instead
        // as we're going to refactor host deployment sync soon so we only have to test this small unit.
        $sync = Model::withoutEvents(function() {
            $sync = new Task([
                'id' => 'sync-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $sync->resource()->associate($this->host());
            return $sync;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\UpdateBilling;
        $UpdateBillingListener->handle(new \App\Events\V2\Task\Updated($sync));

        $metric = BillingMetric::getActiveByKey($this->host(), 'host.hs-test');
        $this->assertNotNull($metric);
        $this->assertEquals(0.00694444, $metric->price);
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }
}

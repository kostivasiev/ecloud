<?php

namespace Tests\unit\Listeners\V2\Host;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Sync;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ToggleHostGroupBillingTest extends TestCase
{
    protected Sync $sync;

    protected Product $product;

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
        factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.0000115314,
        ]);
    }

    public function testCreatingHostEndsHostGroupBilling()
    {
        $this->hostGroup();

        $hostGroupBillingMetric = factory(BillingMetric::class)->create([
            'resource_id' => $this->hostGroup()->id,
            'key' => 'hostgroup',
            'value' => 1,
        ]);

        $this->assertNull($hostGroupBillingMetric->end);

        $this->host();

        $sync = Sync::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $sync->resource()->associate($this->host());
            return $sync;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $this->assertNotNull($hostGroupBillingMetric->refresh()->end);
    }

    public function testDeletingHostEmptyHostGroupStartsBilling()
    {
        $this->hostGroup();
        $this->host();

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);

        $sync = Sync::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'resource_id' => $this->host()->id,
                'completed' => true,
                'type' => Sync::TYPE_DELETE
            ]);
            $sync->resource()->associate($this->host());
            return $sync;
        });

        // Check that the billing metric is added
        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

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
        $this->conjurerServiceMock()->expects('get')
            ->withArgs(['/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test-2'])
            ->andReturnUsing(function () {
                return new Response(200);
            });
        factory(\App\Models\V2\Host::class)->create([
            'id' => 'h-test-2',
            'name' => 'h-test-2',
            'host_group_id' => $this->hostGroup()->id,
        ]);

        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);

        $sync = Sync::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'resource_id' => $this->host()->id,
                'completed' => true,
                'type' => Sync::TYPE_DELETE
            ]);
            $sync->resource()->associate($this->host());
            return $sync;
        });

        $UpdateBillingListener = new \App\Listeners\V2\Host\ToggleHostGroupBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        // Check that no host group billing was added
        $metric = BillingMetric::getActiveByKey($this->hostGroup(), 'hostgroup');
        $this->assertNull($metric);
    }
}

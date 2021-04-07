<?php

namespace Tests\unit\Listeners\V2\Host;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Sync;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateLicenseBillingTest extends TestCase
{
    use DatabaseMigrations;

    protected Sync $sync;

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
        $UpdateBillingListener = new \App\Listeners\V2\Host\UpdateLicenseBilling();
        $UpdateBillingListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $metric = BillingMetric::getActiveByKey($this->host(), 'host.license.windows');
        $this->assertNotNull($metric);
        $this->assertEquals(0.0164384, $metric->price);
        $this->assertNotNull($metric->start);
        $this->assertNull($metric->end);
    }

    public function testCreatingHostInWindowsNotEnabledHostGroupDoesNothing()
    {
        $this->kingpinServiceMock()->expects('get')
            ->with('/api/v2/vpc/vpc-test/hostgroup/hg-test-2')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $hostGroup = factory(HostGroup::class)->create([
            'id' => 'hg-test-2',
            'name' => 'hg-test-2',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => false,
        ]);

    }
}

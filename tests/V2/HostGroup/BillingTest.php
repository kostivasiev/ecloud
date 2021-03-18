<?php
namespace Tests\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class BillingTest extends TestCase
{

    protected Product $product;
    protected ProductPrice $productPrice;
    protected Product $hostProduct;
    protected ProductPrice $hostProductPrice;

    public function setUp(): void
    {
        parent::setUp();
        // Setup HostGroup product
        $this->product = factory(Product::class)->create([
            'product_sales_product_id' => 0,
            'product_name' => $this->availabilityZone()->id.': hostgroup.unallocated',
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        $this->productPrice = factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 9.99,
        ]);
        $productName = $this->availabilityZone()->id . ': host-' . $this->hostSpec()->cpu_cores . '-' .
            $this->hostSpec()->cpu_clock_speed . '-' . $this->hostSpec()->ram_capacity;
        // Setup Host product
        $this->hostProduct = factory(Product::class)->create([
            'product_sales_product_id' => 0,
            'product_name' => $productName,
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_duration_length' => 1,
        ]);
        $this->hostProductPrice = factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->hostProduct->id,
            'product_price_sale_price' => 9.99,
        ]);
    }

    /**
     * @test As a service bill for any existing but unused HostGroups
     */
    public function billForAnExistingButUnusedHostGroup()
    {
        $billingMetric = BillingMetric::where('resource_id', '=', $this->hostGroup()->id)
            ->first();
        $this->assertEquals($this->hostGroup()->id, $billingMetric->resource_id);
        $this->assertEquals($this->hostSpec()->id, $billingMetric->value);
        $this->assertEquals($this->productPrice->product_price_sale_price, $billingMetric->price);
        $this->assertNull($billingMetric->end);
    }

    /**
     * @test As a service stop unused billing for a HostGroup when used
     */
    public function stopUnusedBillingForAHostGroupWhenUsed()
    {
        $this->conjurerServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        // create a host
        $this->host();
        $billingMetric = BillingMetric::where('resource_id', '=', $this->hostGroup()->id)
            ->first();
        $this->assertEquals($this->hostGroup()->id, $billingMetric->resource_id);
        $this->assertEquals($this->hostSpec()->id, $billingMetric->value);
        $this->assertEquals($this->productPrice->product_price_sale_price, $billingMetric->price);
        $this->assertNotNull($billingMetric->end);
    }

    /**
     * @test As a service stop billing for an unused HostGroup when it is deleted
     */
    public function stopUnusedBillingForAHostGroupWhenDeleted()
    {
        $this->hostGroup()->delete();
        $billingMetric = BillingMetric::where('resource_id', '=', $this->hostGroup()->id)
            ->first();
        $this->assertEquals($this->hostGroup()->id, $billingMetric->resource_id);
        $this->assertEquals($this->hostSpec()->id, $billingMetric->value);
        $this->assertEquals($this->productPrice->product_price_sale_price, $billingMetric->price);
        $this->assertNotNull($billingMetric->end);
    }

    /**
     * @test As a service start billing when a host is created
     */
    public function testStartBillingWhenAHostIsCreated()
    {
        $this->conjurerServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->host();

        // Get Metrics
        $billingMetric = BillingMetric::where('resource_id', '=', $this->host()->id)->first();
        $unallocMetric = BillingMetric::where('resource_id', '=', $this->host()->hostGroup->id)->first();

        // Verify the unallocated metric isn't current
        $this->assertEquals($this->hostGroup()->id, $unallocMetric->resource_id);
        $this->assertEquals($this->hostSpec()->id, $unallocMetric->value);
        $this->assertEquals($this->productPrice->product_price_sale_price, $unallocMetric->price);
        $this->assertNotNull($unallocMetric->end);

        // Verify the host metric is current
        $this->assertEquals($this->host()->id, $billingMetric->resource_id);
        $this->assertEquals($this->hostSpec()->id, $billingMetric->value);
        $this->assertEquals($this->hostProductPrice->product_price_sale_price, $billingMetric->price);
        $this->assertNull($billingMetric->end);
    }

    /**
     * @test Revert to Unused Billing when Host is deleted but HostGroup kept
     */
    public function revertToUnusedBillingWhenHostDeleted()
    {
        $this->markTestSkipped('revertToUnusedBillingWhenHostDeleted - to do');
    }
}
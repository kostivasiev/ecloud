<?php
namespace Tests\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Sync;
use Tests\TestCase;

class BillingTest extends TestCase
{

    protected Product $product;
    protected ProductPrice $productPrice;

    public function setUp(): void
    {
        parent::setUp();
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
}
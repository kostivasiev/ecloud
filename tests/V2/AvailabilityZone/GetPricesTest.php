<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetPricesTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected LoadBalancerCluster $lbc;
    protected Router $router;
    protected Vpc $vpc;
    protected AvailabilityZone $availabilityZone;
    protected Product $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->product = factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': vcpu'
        ]);

        factory(ProductPrice::class)->create([
            'product_price_product_id' => $this->product->id,
            'product_price_sale_price' => 0.999
        ]);

        factory(ProductPriceCustom::class)->create([
            'product_price_custom_product_id' => $this->product->id,
            'product_price_custom_reseller_id' => 1,
            'product_price_custom_sale_price' => 0.111
        ]);
    }

    public function testGetPrices()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone()->id . '/prices',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'availability_zone_id'   => $this->product->availability_zone_id,
                'name' => $this->product->name,
                'category'  => strtolower($this->product->product_subcategory),
                'price'  => 0.999,
            ])->assertResponseStatus(200);
    }

    public function testGetCustomPrices()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone()->id . '/prices',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'availability_zone_id'   => $this->product->availability_zone_id,
                'name' => $this->product->name,
                'category'  => strtolower($this->product->product_subcategory),
                'price'  => 0.111,
            ])->assertResponseStatus(200);
    }
}

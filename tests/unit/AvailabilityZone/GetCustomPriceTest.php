<?php

namespace Tests\unit\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetCustomPriceTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = Region::factory()->create();

        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $region->id
        ])->each(function ($availabilityZone) {
            Product::factory()->create([
                'product_name' => $availabilityZone->id . ': ' . $this->faker->word,
            ])->each(function ($product) {
                ProductPrice::factory()->create([
                    'product_price_product_id' => $product->id,
                    'product_price_sale_price' => 0.999
                ])->each(function ($productPrice) {
                    ProductPriceCustom::factory()->create([
                        'product_price_custom_product_id' => $productPrice->product_price_product_id,
                        'product_price_custom_reseller_id' => 1,
                        'product_price_custom_sale_price' => 0.11111
                    ]);
                });
            });
        });
    }

    public function testGetCustomPrice()
    {
        $product = Product::first();

        $this->assertEquals(0.999, $product->getPrice());

        $resellerId = 0;
        $this->assertEquals(0.999, $product->getPrice($resellerId));

        $resellerId = 1;
        $this->assertEquals(0.11111, $product->getPrice($resellerId));
    }
}

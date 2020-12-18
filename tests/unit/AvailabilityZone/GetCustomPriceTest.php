<?php

namespace Tests\unit\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetCustomPriceTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ])->each(function ($availabilityZone) {
            factory(Product::class)->create([
                'product_name' => $availabilityZone->getKey() . ': ' . $this->faker->word,
            ])->each(function ($product) {
                factory(ProductPrice::class)->create([
                    'product_price_product_id' => $product->getKey(),
                    'product_price_sale_price' => 0.999
                ])->each(function ($productPrice) {
                    factory(ProductPriceCustom::class)->create([
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

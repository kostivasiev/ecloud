<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetPricesTest extends TestCase
{
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': ' . $this->faker->word(),
        ])->each(function ($product) {
            ProductPrice::factory()->create([
                'product_price_product_id' => $product->id
            ]);
        });
    }

    public function testGetPrices()
    {
        $product = Product::where('product_name', 'like', $this->availabilityZone()->id . '%')->first();

        $this->get(
            '/v2/regions/' . $this->region()->id . '/prices',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'availability_zone_id'   => $product->availability_zone_id,
                'name' => $product->name,
                'category'  => strtolower($product->product_subcategory),
                'price'  => $product->getPrice(),
                'rate'  => strtolower($product->product_duration_type),
            ])->assertStatus(200);
    }
}

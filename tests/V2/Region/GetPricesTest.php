<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetPricesTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected LoadBalancerCluster $lbc;
    protected Router $router;
    protected Vpc $vpc;
    protected Region $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();

        $this->availabilityZones = factory(AvailabilityZone::class, 2)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->availabilityZones->each(function ($availabilityZone) {
            factory(Product::class, 5)->create([
                'product_name' => $availabilityZone->getKey() . ': ' . $this->faker->word,
            ])->each(function ($product) {
                factory(ProductPrice::class)->create([
                    'product_price_product_id' => $product->getKey()
                ]);
            });
        });
    }

    public function testGetPrices()
    {
        $product = Product::where('product_name', 'like', $this->availabilityZones->first()->getKey() . '%')->first();
        
        $this->get(
            '/v2/regions/' . $this->region->getKey() . '/prices',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'availability_zone_id'   => $product->availability_zone_id,
                'name' => $product->name,
                'category'  => strtolower($product->product_subcategory),
                'price'  => $product->getPrice(),
                'rate'  => strtolower($product->product_duration_type),
            ])->assertResponseStatus(200);
    }
}

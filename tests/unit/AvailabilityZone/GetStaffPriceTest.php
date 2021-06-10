<?php

namespace Tests\unit\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetStaffPriceTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ])->each(function ($availabilityZone) {
            factory(Product::class)->create([
                'product_name' => $availabilityZone->id . ': ' . $this->faker->word,
                'product_cost_price' => 0.1111 // Staff price
            ])->each(function ($product) {
                factory(ProductPrice::class)->create([
                    'product_price_product_id' => $product->id,
                    'product_price_sale_price' => 0.9999 // Normal price
                ])->each(function ($productPrice) {
                    factory(ProductPriceCustom::class)->create([
                        'product_price_custom_product_id' => $productPrice->product_price_product_id,
                        'product_price_custom_reseller_id' => 1,
                        'product_price_custom_sale_price' => 0.2222 // Custom price (reseller 1)
                    ]);
                });
            });
        });
    }

    public function testInternalAccountPriceIsZero()
    {
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => 'Internal Account'
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $product = Product::first();

        $resellerId = 12345;
        $this->assertEquals(0.00, $product->getPrice($resellerId));
    }

    public function testStaffPrice()
    {
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => 'Staff'
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $product = Product::first();

        $resellerId = 12345;
        $this->assertEquals(0.1111, $product->getPrice($resellerId));
    }

    public function testCustomPrice()
    {
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => 'Staff'
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $product = Product::first();

        $resellerId = 1;
        $this->assertEquals(0.2222, $product->getPrice($resellerId));
    }
}

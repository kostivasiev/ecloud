<?php

namespace App\Console\Commands\Billing\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\RouterThroughput;
use Illuminate\Console\Command;

class ProductPopulation extends Command
{
    protected $signature = 'billing:router-throughput:product-population';

    protected $description = 'Creates missing product records for router throughput billing per availability zone';

    public function handle()
    {
        AvailabilityZone::all()->each(function ($availabilityZone) {
            RouterThroughput::where('availability_zone_id', $availabilityZone->id)->each(function ($routerThroughput) {
                /** @var RouterThroughput $routerThroughput */
                $productName = $routerThroughput->availabilityZone->id . ': throughput ' . $routerThroughput->name;
                if ($routerThroughput->availabilityZone
                    ->products()
                    ->where('product_name', $productName)
                    ->count()
                ) {
                    $this->info('Found product "' . $productName . '", skipping creation');
                    return;
                }

                $product = app()->make(Product::class);
                $product->fill([
                    'product_sales_product_id' => 0,
                    'product_name' => $productName,
                    'product_category' => 'eCloud',
                    'product_subcategory' => 'Networking',
                    'product_supplier' => 'UKFast',
                    'product_active' => 'Yes',
                    'product_duration_type' => 'Hour',
                    'product_duration_length' => 1,
                    'product_cost_currency' => '',
                    'product_cost_price' => 0.0,
                ]);
                $product->save();

                $product_price = app()->make(ProductPrice::class);
                $product_price->fill([
                    'product_price_product_id' => $product->product_id,
                    'product_price_type' => 'Standard',
                    'product_price_sale_price' => 0.01,
                ]);
                $product_price->save();

                $this->info('Created product "' . $productName . '" (' . $product->product_id . ')');
            });
        });

        return Command::SUCCESS;
    }
}

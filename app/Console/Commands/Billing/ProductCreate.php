<?php

namespace App\Console\Commands\Billing;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use Illuminate\Console\Command;

class ProductCreate extends Command
{
    protected $signature = 'billing:product-create';

    protected $description = 'Creates billing products';

    public function handle()
    {
        $availabilityZones = AvailabilityZone::query();

        $availabilityZoneId = $this->ask('Please enter the availability zone ID (leave blank for all AZ\'s)');

        if (!empty($availabilityZoneId)) {
            $availabilityZones->where('id', $availabilityZoneId);
            $this->line('Adding billing product for availability zone ' . $availabilityZoneId);
        }

        $productName = $this->ask('Please enter the product name');

        $this->line($productName);

        $category = $this->choice(
            'Select product category',
            [
                'Compute',
                'Storage',
                'Networking',
                'License',
                'Support',
                'Dedicated Hosts'
            ],
            0
        );

        $this->line($category);

        $price = $this->ask('Please enter the product hourly price', 0);

        $insertOrSql = $this->choice(
            'Insert records or display SQL',
            ['Insert Records', 'Display SQL Only'],
            0
        );

        switch ($insertOrSql) {
            case 'Insert Records':
                $availabilityZones->each(function ($availabilityZone) use ($productName, $category, $price) {
                    $name = $availabilityZone->id . ': ' . $productName;

                    if ($availabilityZone
                        ->products()
                        ->where('product_name', $name)
                        ->count()
                    ) {
                        $this->info('Found product "' . $name . '", skipping');
                        return;
                    }

                    $product = app()->make(Product::class);
                    $product->fill([
                        'product_sales_product_id' => 0,
                        'product_name' => $name,
                        'product_category' => 'eCloud',
                        'product_subcategory' => $category,
                        'product_supplier' => 'UKFast',
                        'product_active' => 'Yes',
                        'product_duration_type' => 'Hour',
                        'product_duration_length' => 1,
                        'product_cost_currency' => 'GBP',
                        'product_cost_price' => 0,
                    ]);
                    $product->save();

                    $product_price = app()->make(ProductPrice::class);
                    $product_price->fill([
                        'product_price_product_id' => $product->product_id,
                        'product_price_type' => 'Standard',
                        'product_price_sale_price' => $price,
                    ]);
                    $product_price->save();

                    $this->info('Created product "' . $name . '" (' . $product->product_id . ')');
                });
                break;
            case 'Display SQL Only':
                $availabilityZones->each(function ($availabilityZone) use ($productName, $category, $price) {
                    $name = $availabilityZone->id . ': ' . $productName;

                    $sql = <<<EOM
INSERT INTO `product` (product_sales_product_id,
                       product_name,
                       product_category,
                       product_subcategory,
                       product_supplier,
                       product_active,
                       product_duration_type,
                       product_duration_length,
                       product_cost_currency,
                       product_cost_price)
VALUES (0,
        '$name',
        'eCloud',
        '$category',
        'UKFast',
        'Yes',
        'Hour',
        1,
        'GBP',
        0);

INSERT INTO `product_price`
(product_price_product_id,
 product_price_sale_price)
VALUES (LAST_INSERT_ID(),
        '$price');                    
EOM;

                    $this->info($sql . PHP_EOL);
                });

                break;
        }

        return Command::SUCCESS;
    }
}

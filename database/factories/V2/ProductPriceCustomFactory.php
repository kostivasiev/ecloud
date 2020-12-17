<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpc;
use Faker\Generator as Faker;

$factory->define(\App\Models\V2\ProductPriceCustom::class, function (Faker $faker) {
    return [
        'product_price_custom_reseller_id' => 1,
        'product_price_custom_sale_price' => 0.09,
    ];
});

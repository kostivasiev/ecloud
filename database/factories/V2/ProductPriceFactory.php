<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpc;
use Faker\Generator as Faker;

$factory->define(\App\Models\V2\ProductPrice::class, function (Faker $faker) {
    return [
        'product_price_type' => 'Standard',
        'product_price_sale_price' => $faker->randomFloat(8, 0.001, 0.1),
    ];
});

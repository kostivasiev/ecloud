<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Models\V2\ProductPrice::class, function () {
    return [
        'product_price_type' => 'Standard',
        'product_price_sale_price' => 0.1,
    ];
});

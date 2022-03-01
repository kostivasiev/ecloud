<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Models\V2\ProductPriceCustom::class, function () {
    return [
        'product_price_custom_reseller_id' => 1,
        'product_price_custom_sale_price' => 0.09,
    ];
});

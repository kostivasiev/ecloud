<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */


$factory->define(\App\Models\V2\Product::class, function () {
    return [
        'product_name' => 'az-aaaaaaaa: vcpu-1',
        'product_category' => 'eCloud',
        'product_subcategory' => 'Compute',
        'product_supplier' => 'UKFast',
        'product_active' => 'Yes',
        'product_duration_type' => 'Hour',
        'product_cost_price' => 0.00
    ];
});

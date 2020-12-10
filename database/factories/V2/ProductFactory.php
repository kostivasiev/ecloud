<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpc;
use Faker\Generator as Faker;

$factory->define(\App\Models\V2\Product::class, function (Faker $faker) {
    return [
        'product_name' => 'az-aaaaaaaa: vcpu-1',
        'product_category' => 'eCloud',
        'product_subcategory' => 'Compute',
        'product_supplier' => 'UKFast',
        'product_active' => 'Yes',
        'product_duration_type' => 'Hour'
    ];
});

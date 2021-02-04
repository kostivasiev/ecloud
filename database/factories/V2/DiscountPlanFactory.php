<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\DiscountPlan;

$factory->define(DiscountPlan::class, function () {
    return [
        'reseller_id' => 1,
        'name' => 'test-commitment',
        'commitment_amount' => '2000',
        'commitment_before_discount' => '1000',
        'discount_rate' => '5',
        'term_length' => '24',
        'term_start_date' => date('Y-m-d H:i:s', strtotime('now')),
        'term_end_date' => date('Y-m-d H:i:s', strtotime('2 days')),
    ];
});

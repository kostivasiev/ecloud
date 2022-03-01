<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\BillingMetric;

$factory->define(BillingMetric::class, function () {
    return [
        'resource_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'vpc_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'reseller_id' => 1,
        'name' => 'RAM (per Megabyte)',
        'key' => 'ram.capacity',
        'value' => '16GB',
        'start' => '2020-07-07T10:30:00+01:00',
    ];
});

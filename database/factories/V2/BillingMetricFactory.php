<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\BillingMetric;
use Faker\Generator as Faker;

$factory->define(BillingMetric::class, function (Faker $faker) {
    return [
        'resource_id' => $faker->uuid,
        'vpc_id' => $faker->uuid,
        'reseller_id' => 1,
        'key' => 'ram.capacity',
        'value' => '16GB',
        'cost' => '5.55',
        'start' => '2020-07-07T10:30:00+01:00',
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Models\V2\AvailabilityZoneCapacity::class, function (Faker $faker) {
    return [
        'type' => 'floating_ip',
        'current' => 20,
        'alert_warning' => 60,
        'alert_critical' => 80,
        'max' => 95,
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FloatingIp;
use Faker\Generator as Faker;

$factory->define(FloatingIp::class, function (Faker $faker) {
    return [
        'ip_address' => '1.1.1.1'
    ];
});

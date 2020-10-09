<?php

/** @var Factory $factory */

use App\Models\V2\FloatingIp;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(FloatingIp::class, function (Faker $faker) {
    return [
        'ip_address' => '1.1.1.1'
    ];
});

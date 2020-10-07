<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Nic;
use Faker\Generator as Faker;

$factory->define(Nic::class, function (Faker $faker) {
    return [
        'ip_address' => '10.0.0.5'
    ];
});

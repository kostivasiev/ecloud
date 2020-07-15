<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpns;
use Faker\Generator as Faker;

$factory->define(Vpns::class, function (Faker $faker) {
    return [
        'id'   => Vpns::generateId(new Vpns()),
    ];
});

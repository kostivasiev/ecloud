<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Dhcps;
use Faker\Generator as Faker;

$factory->define(Dhcps::class, function (Faker $faker) {
    return [
        'id'   => Dhcps::generateId(new Dhcps()),
    ];
});

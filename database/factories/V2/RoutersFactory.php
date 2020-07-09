<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Routers;
use Faker\Generator as Faker;

$factory->define(Routers::class, function (Faker $faker) {
    return [
        'name'       => 'Primary Router',
    ];
});

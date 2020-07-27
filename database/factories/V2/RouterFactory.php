<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Router;
use Faker\Generator as Faker;

$factory->define(Router::class, function (Faker $faker) {
    return [
        'name'       => 'Primary Router',
        'vpc_id' => 'vdc-a7d7c4e6'
    ];
});

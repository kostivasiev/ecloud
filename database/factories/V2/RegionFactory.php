<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Region;
use Faker\Generator as Faker;

$factory->define(Region::class, function (Faker $faker) {
    return [
        'name' => 'United Kingdom'
    ];
});

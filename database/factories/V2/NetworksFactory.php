<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Networks;
use Faker\Generator as Faker;

$factory->define(Networks::class, function (Faker $faker) {
    return [
        'id'   => Networks::generateId(new Networks()),
        'name' => 'My Gateway 1'
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Gateway;
use Faker\Generator as Faker;

$factory->define(Gateway::class, function (Faker $faker) {
    return [
        'id'   => Gateway::generateId(new Gateway()),
        'name' => 'My Gateway 1'
    ];
});

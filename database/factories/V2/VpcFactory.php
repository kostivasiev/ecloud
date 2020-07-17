<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpc;
use Faker\Generator as Faker;

$factory->define(Vpc::class, function (Faker $faker) {
    return [
        'id'   => Vpc::generateId(new Vpc()),
        'name' => 'Virtual Private Cloud Name',
    ];
});
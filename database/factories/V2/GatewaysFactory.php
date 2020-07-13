<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Gateways;
use Faker\Generator as Faker;

$factory->define(Gateways::class, function (Faker $faker) {
    return [
        'id'   => Gateways::generateId(new Gateways()),
        'name' => 'My Gateway 1'
    ];
});

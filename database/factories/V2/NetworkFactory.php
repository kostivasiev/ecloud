<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Network;
use Faker\Generator as Faker;

$factory->define(Network::class, function (Faker $faker) {
    return [
        'id'   => Network::generateId(new Network()),
        'name' => 'My Gateway 1'
    ];
});

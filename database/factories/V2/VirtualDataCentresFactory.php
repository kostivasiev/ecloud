<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VirtualPrivateClouds;
use Faker\Generator as Faker;

$factory->define(VirtualPrivateClouds::class, function (Faker $faker) {
    return [
        'id'   => VirtualPrivateClouds::generateId(new VirtualPrivateClouds()),
        'name' => 'Virtual Private Cloud Name',
    ];
});

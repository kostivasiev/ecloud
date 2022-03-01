<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\RouterThroughput;
use Faker\Generator as Faker;

$factory->define(RouterThroughput::class, function (Faker $faker) {
    return [
        'name' => '10Gb',
        'availability_zone_id' => 'az-aaaaaaaa',
        "committed_bandwidth" => 10240,
    ];
});

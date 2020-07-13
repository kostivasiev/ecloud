<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VirtualDataCentres;
use Faker\Generator as Faker;

$factory->define(VirtualDataCentres::class, function (Faker $faker) {
    return [
        'id'   => VirtualDataCentres::generateId(new VirtualDataCentres()),
        'name' => 'Virtual Datacentre Name',
    ];
});

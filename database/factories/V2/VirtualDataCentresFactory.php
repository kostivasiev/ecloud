<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VirtualDataCentres;
use Faker\Generator as Faker;
use Ramsey\Uuid\Uuid;

$factory->define(VirtualDataCentres::class, function (Faker $faker) {
    return [
        'id'   => Uuid::uuid4()->toString(),
        'name' => 'Virtual Datacentre Name',
    ];
});

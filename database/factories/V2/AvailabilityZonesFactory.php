<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\AvailabilityZones;
use Faker\Generator as Faker;
use Ramsey\Uuid\Uuid;

$factory->define(AvailabilityZones::class, function (Faker $faker) {
    return [
        'id'      => Uuid::uuid4()->toString(),
        'code'    => 'LON1',
        'name'    => 'London Zone 1',
        'site_id' => 1,
    ];
});

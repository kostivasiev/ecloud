<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\AvailabilityZone;
use Faker\Generator as Faker;

$factory->define(AvailabilityZone::class, function (Faker $faker) {
    return [
        'id'      => AvailabilityZone::generateId(new AvailabilityZone()),
        'code'    => 'LON1',
        'name'    => 'London Zone 1',
        'site_id' => 1,
    ];
});

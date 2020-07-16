<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\AvailabilityZones;
use Faker\Generator as Faker;

$factory->define(AvailabilityZones::class, function (Faker $faker) {
    return [
        'id'      => AvailabilityZones::generateId(new AvailabilityZones()),
        'code'    => 'LON1',
        'name'    => 'London Zone 1',
        'site_id' => 1,
        'nsx_manager_endpoint' => 'https://www.ukfast.co.uk/',
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Volume;
use Faker\Generator as Faker;

$factory->define(Volume::class, function (Faker $faker) {
    return [
        'name' => 'Primary Volume',
        'capacity' => '100',
        'availability_zone_id' => 'avz-716d7f96',
        'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
    ];
});

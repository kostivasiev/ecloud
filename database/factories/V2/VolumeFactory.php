<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Volume;

$factory->define(Volume::class, function () {
    return [
        'name' => 'Primary Volume',
        'capacity' => '100',
        'availability_zone_id' => 'avz-716d7f96',
        'vmware_uuid' => '03747ccf-d56b-45a9-b589-177f3cb9936e'
    ];
});

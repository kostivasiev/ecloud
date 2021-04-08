<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Volume;

$factory->define(Volume::class, function () {
    return [
        'name' => 'Primary Volume',
        'capacity' => '100',
    ];
});

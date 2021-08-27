<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VolumeGroup;

$factory->define(VolumeGroup::class, function () {
    return [
        'name' => 'Primary Volume Group',
    ];
});

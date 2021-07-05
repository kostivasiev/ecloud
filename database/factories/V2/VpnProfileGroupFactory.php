<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnProfileGroup;

$factory->define(VpnProfileGroup::class, function () {
    return [
        'name' => 'Test Profile Group',
        'description' => 'Profile group description',
    ];
});

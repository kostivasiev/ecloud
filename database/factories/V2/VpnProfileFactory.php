<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnProfile;

$factory->define(VpnProfile::class, function () {
    return [
        'name' => 'VPN Profile',
    ];
});

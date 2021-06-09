<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpn;

$factory->define(Vpn::class, function () {
    return [
        'name' => 'Office VPN',
    ];
});

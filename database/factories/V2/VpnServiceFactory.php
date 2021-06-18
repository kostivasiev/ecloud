<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnService;

$factory->define(VpnService::class, function () {
    return [
        'name' => 'Office VPN',
    ];
});

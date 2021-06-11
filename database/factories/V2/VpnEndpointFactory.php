<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnEndpoint;

$factory->define(VpnEndpoint::class, function () {
    return [
        'name' => 'Endpoint Name',
    ];
});
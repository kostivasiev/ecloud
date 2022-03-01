<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FloatingIp;

$factory->define(FloatingIp::class, function () {
    return [
        'ip_address' => '1.1.1.1'
    ];
});

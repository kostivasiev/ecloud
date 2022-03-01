<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Nic;

$factory->define(Nic::class, function () {
    return [
        'mac_address' => '01-23-45-67-89-AB',
        'instance_id' => 'i-' . bin2hex(random_bytes(4)),
        'network_id' => 'net-' . bin2hex(random_bytes(4)),
    ];
});

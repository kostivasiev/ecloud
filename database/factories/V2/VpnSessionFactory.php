<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnSession;

$factory->define(VpnSession::class, function () {
    return [
        'remote_ip' => '218.16.12.11',
        'remote_networks' => '172.12.23.11/32',
        'local_networks' => '172.11.11.11/32,176.18.22.11/24, 127.1.10.1/24',
    ];
});

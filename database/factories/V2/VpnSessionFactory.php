<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnSession;

$factory->define(VpnSession::class, function () {
    return [
        'remote_ip' => '218.16.12.11',
    ];
});

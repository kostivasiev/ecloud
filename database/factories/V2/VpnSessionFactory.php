<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnSession;

$factory->define(VpnSession::class, function () {
    return [
        'name' => 'Office Session',
    ];
});

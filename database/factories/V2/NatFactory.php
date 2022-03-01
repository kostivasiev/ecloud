<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Nat;

$factory->define(Nat::class, function () {
    return [
        'destination_id' => 'fip-123456',
        'translated_id' => 'nic-654321',
        'action' => 'DNAT'
    ];
});

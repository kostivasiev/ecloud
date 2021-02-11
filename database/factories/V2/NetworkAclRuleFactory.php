<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\NetworkAclRule;

$factory->define(NetworkAclRule::class, function () {
    return [
        'sequence' => 1,
        'source' => '10.0.1.0/32',
        'destination' => '10.0.2.0/32',
        'action' => 'ALLOW',
        'enabled' => true,
    ];
});

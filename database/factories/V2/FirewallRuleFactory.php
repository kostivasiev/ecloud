<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRule;

$factory->define(FirewallRule::class, function () {
    return [
        'name' => 'name',
        'sequence' => 10,
        'source' => '192.168.100.1',
        'destination' => '212.22.18.10',
        'action' => 'ALLOW',
        'direction' => 'IN',
        'enabled' => true
    ];
});

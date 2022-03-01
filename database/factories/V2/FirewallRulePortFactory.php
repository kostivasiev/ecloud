<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRulePort;

$factory->define(FirewallRulePort::class, function () {
    return [
        'name' => 'name',
        'protocol' => 'TCP',
        'source' => '443',
        'destination' => '555',
    ];
});

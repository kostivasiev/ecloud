<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\NetworkAclRulePort;

$factory->define(NetworkAclRulePort::class, function () {
    return [
        'protocol' => 'TCP',
        'source' => '443',
        'destination' => '555',
    ];
});

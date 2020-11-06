<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRulePort;
use Faker\Generator as Faker;

$factory->define(FirewallRulePort::class, function (Faker $faker) {
    return [
        'name' => $faker->domainWord,
        'protocol' => 'TCP',
        'source' => '192.168.100.1',
        'destination' => '212.22.18.10',
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRulePort;
use Faker\Generator as Faker;

$factory->define(FirewallRulePort::class, function (Faker $faker) {
    return [
        'name' => $faker->domainWord,
        'protocol' => 'TCP',
        'source' => '443',
        'destination' => '555',
    ];
});
